<?php

namespace App\Services;

use App\Models\LoanPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RepaymentIndexService
{
    public function filters(Request $request): array
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');
        $sort = (string) $request->input('sort', 'newest');

        if (! array_key_exists($status, $this->statusOptions())) {
            $status = 'all';
        }

        if (! array_key_exists($sort, $this->sortOptions())) {
            $sort = 'newest';
        }

        return compact('search', 'status', 'sort');
    }

    public function statusOptions(): array
    {
        return [
            'all' => __('repayments.filter_all'),
            'active' => __('repayments.filter_active'),
            'cleared' => __('repayments.filter_cleared'),
            'grace' => __('repayments.filter_grace'),
        ];
    }

    public function sortOptions(): array
    {
        return [
            'newest' => __('dashboard.sort_newest'),
            'oldest' => __('dashboard.sort_oldest'),
            'outstanding_high' => __('repayments.sort_outstanding_high'),
            'paid_high' => __('repayments.sort_paid_high'),
        ];
    }

    public function query(array $filters): Builder
    {
        $query = LoanPayment::query()->with(['loan.applicant']);

        if ($filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';
            $query->where(function (Builder $q) use ($term) {
                $q->whereHas('loan', function (Builder $loan) use ($term) {
                    $loan->withoutGlobalScopes()
                        ->where('loan_track_id', 'like', $term)
                        ->orWhereHas('applicant', function (Builder $applicant) use ($term) {
                            $applicant->withoutGlobalScopes()
                                ->where('full_name', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                });
            });
        }

        match ($filters['status']) {
            'active' => $query->where('outstanding_debt', '>', 0),
            'cleared' => $query->where(function (Builder $q) {
                $q->where('outstanding_debt', '<=', 0)->orWhereNull('outstanding_debt');
            }),
            'grace' => $this->applyGraceFilter($query),
            default => null,
        };

        match ($filters['sort']) {
            'oldest' => $query->oldest('id'),
            'outstanding_high' => $query->orderByDesc('outstanding_debt'),
            'paid_high' => $query->orderByDesc('amount_paid'),
            default => $query->latest('id'),
        };

        return $query;
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    public function summary(array $filters): array
    {
        $base = $this->query($filters)->clone()->reorder();

        return [
            'count' => (clone $base)->count(),
            'active_count' => (clone $base)->where('outstanding_debt', '>', 0)->count(),
            'cleared_count' => (clone $base)->where(function (Builder $q) {
                $q->where('outstanding_debt', '<=', 0)->orWhereNull('outstanding_debt');
            })->count(),
            'total_disbursed' => (float) (clone $base)->sum('amount_disbursed'),
            'total_paid' => (float) (clone $base)->sum('amount_paid'),
            'total_outstanding' => (float) (clone $base)->sum('outstanding_debt'),
        ];
    }

    public function exportRows(array $filters): Collection
    {
        return $this->query($filters)->get()->map(function (LoanPayment $payment) {
            return [
                'track_id' => $payment->loan?->loan_track_id ?? '—',
                'name' => $payment->loan?->applicant?->full_name ?? '—',
                'disbursed' => (float) $payment->amount_disbursed,
                'paid' => (float) $payment->amount_paid,
                'outstanding' => (float) $payment->outstanding_debt,
                'status' => $this->statusLabel($payment),
                'start_date' => optional($payment->start_date)?->toDateString() ?? '—',
            ];
        });
    }

    public function statusLabel(LoanPayment $payment): string
    {
        if ((float) $payment->outstanding_debt <= 0) {
            return __('repayments.filter_cleared');
        }

        if ($payment->isInGracePeriod()) {
            return __('repayments.filter_grace');
        }

        return __('repayments.filter_active');
    }

    public function statusVariant(LoanPayment $payment): string
    {
        if ((float) $payment->outstanding_debt <= 0) {
            return 'success';
        }

        if ($payment->isInGracePeriod()) {
            return 'warning';
        }

        return 'info';
    }

    protected function applyGraceFilter(Builder $query): void
    {
        $query->where('outstanding_debt', '>', 0)
            ->whereNotNull('start_date');

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $query->whereRaw("date(start_date, '+' || grace_period_days || ' days') > date('now')");

            return;
        }

        $query->whereRaw('DATE_ADD(start_date, INTERVAL grace_period_days DAY) > CURDATE()');
    }
}
