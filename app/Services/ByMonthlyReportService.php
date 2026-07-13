<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Scopes\ApprovalLevelScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ByMonthlyReportService
{
    public const MONTHS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    public const SORTS = [
        'newest',
        'oldest',
        'name_asc',
        'disbursed_desc',
        'paid_desc',
        'outstanding_desc',
    ];

    public function __construct(private GeoHierarchyService $geo) {}

    public function normalizeFilters(array $input): array
    {
        $sort = $input['sort'] ?? 'newest';
        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'newest';
        }

        $month = isset($input['month']) && $input['month'] !== ''
            ? (int) $input['month']
            : null;
        if ($month !== null && ! in_array($month, self::MONTHS, true)) {
            $month = null;
        }

        $customFrom = $input['date_from'] ?? null;
        $customTo = $input['date_to'] ?? null;
        $useCustomDates = filled($customFrom)
            && filled($customTo)
            && ($input['use_custom_dates'] ?? null) === '1';

        $from = null;
        $to = null;

        if ($useCustomDates) {
            $from = (string) $customFrom;
            $to = (string) $customTo;
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
        }

        return $this->geo->clampGeoFilters([
            'month' => $month,
            'date_from' => $from,
            'date_to' => $to,
            'region_id' => null,
            'district_id' => null,
            'council_id' => null,
            'ward_id' => null,
            'street_id' => null,
            'sort' => $sort,
            'use_custom_dates' => $useCustomDates ? '1' : null,
        ]);
    }

    public function monthOptions(): array
    {
        return collect(self::MONTHS)
            ->mapWithKeys(fn (int $month) => [$month => __('by_monthly_reports.month_'.$month)])
            ->all();
    }

    public function summary(array $filters): array
    {
        $loans = $this->baseQuery($filters)
            ->with(['loanPayments', 'group.members:id,loan_group_id'])
            ->get();

        $individual = $loans->where('loan_type', 'individual');
        $group = $loans->where('loan_type', 'group');

        $disbursed = 0.0;
        $paid = 0.0;
        $outstanding = 0.0;
        $groupMembers = 0;

        foreach ($loans as $loan) {
            $payment = $this->paymentLedger($loan);
            $disbursed += $this->actualDisbursedAmount($loan);
            $paid += (float) ($payment?->amount_paid ?? 0);
            $outstanding += $this->outstandingAmount($loan, $payment);

            if ($loan->loan_type === 'group') {
                $groupMembers += $loan->group?->members?->count() ?? 0;
            }
        }

        return [
            'count' => $loans->count(),
            'individual_count' => $individual->count(),
            'group_count' => $group->count(),
            'group_members_count' => $groupMembers,
            'total_disbursed' => round($disbursed, 2),
            'total_paid' => round($paid, 2),
            'total_outstanding' => round($outstanding, 2),
        ];
    }

    public function paginatedRows(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Loan $loan) => $this->mapRow($loan));
    }

    public function allRows(array $filters): Collection
    {
        $query = $this->baseQuery($filters);
        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query->get()->map(fn (Loan $loan) => $this->mapRow($loan));
    }

    public function exportFilename(string $extension): string
    {
        return 'wdf-by-monthly-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = $this->scopedLoanQuery()
            ->with([
                'applicant:id,full_name,phone',
                'group:id,name,phone',
                'businessDetails.region:id,name',
                'loanPayments',
            ])
            ->where('status', 'disbursed')
            ->where('disbursed_amount', '>', 0);

        if ($filters['date_from']) {
            $query->where(function (Builder $q) use ($filters) {
                $q->whereDate('date_issued', '>=', $filters['date_from'])
                    ->orWhere(function (Builder $inner) use ($filters) {
                        $inner->whereNull('date_issued')
                            ->whereDate('updated_at', '>=', $filters['date_from']);
                    });
            });
        }

        if ($filters['date_to']) {
            $query->where(function (Builder $q) use ($filters) {
                $q->whereDate('date_issued', '<=', $filters['date_to'])
                    ->orWhere(function (Builder $inner) use ($filters) {
                        $inner->whereNull('date_issued')
                            ->whereDate('updated_at', '<=', $filters['date_to']);
                    });
            });
        }

        if (! empty($filters['month']) && empty($filters['use_custom_dates'])) {
            $month = (int) $filters['month'];
            $query->where(function (Builder $q) use ($month) {
                $q->whereMonth('date_issued', $month)
                    ->orWhere(function (Builder $inner) use ($month) {
                        $inner->whereNull('date_issued')
                            ->whereMonth('updated_at', $month);
                    });
            });
        }

        return $query;
    }

    protected function scopedLoanQuery(): Builder
    {
        $user = Auth::user();
        $query = Loan::withoutGlobalScope(ApprovalLevelScope::class);

        if ($user?->hasRole('applicant')) {
            $query->where('user_id', $user->id);
        } elseif ($user?->hasRole(['cdo_ward', 'cdo_council', 'cdo_region'])) {
            app(CdoLoanScopeService::class)->applyBusinessDetailsScope($query, $user);
        }

        return $query;
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest('date_issued')->oldest('id'),
            'name_asc' => $query
                ->orderByRaw("COALESCE(
                    (SELECT full_name FROM applicants WHERE applicants.id = loans.applicant_id LIMIT 1),
                    (SELECT name FROM loan_groups WHERE loan_groups.id = loans.loan_group_id LIMIT 1),
                    ''
                ) ASC")
                ->orderBy('id'),
            'disbursed_desc' => $query->orderByDesc('disbursed_amount')->orderByDesc('id'),
            'paid_desc' => $query
                ->orderByDesc(
                    LoanPayment::select('amount_paid')
                        ->whereColumn('loan_payments.loan_id', 'loans.id')
                        ->orderBy('id')
                        ->limit(1)
                )
                ->orderByDesc('id'),
            'outstanding_desc' => $query
                ->orderByDesc(
                    LoanPayment::select('outstanding_debt')
                        ->whereColumn('loan_payments.loan_id', 'loans.id')
                        ->orderBy('id')
                        ->limit(1)
                )
                ->orderByDesc('id'),
            default => $query->latest('date_issued')->latest('id'),
        };
    }

    protected function mapRow(Loan $loan): array
    {
        $payment = $this->paymentLedger($loan);
        $isGroup = $loan->loan_type === 'group';
        $ref = $loan->date_issued ?? $loan->updated_at;

        return [
            'track_id' => $loan->loan_track_id,
            'hashid' => $loan->hashid,
            'name' => $isGroup
                ? ($loan->group?->name ?? __('common.na'))
                : ($loan->applicant?->full_name ?? __('common.na')),
            'loan_type' => $loan->loan_type,
            'loan_type_label' => loan_type_label($loan->loan_type),
            'phone' => $isGroup
                ? ($loan->group?->phone ?: __('common.na'))
                : ($loan->applicant?->phone ?: __('common.na')),
            'region' => $loan->businessDetails?->region?->name ?? __('reports.unknown_region'),
            'month_label' => $ref ? __('by_monthly_reports.month_'.(int) $ref->format('n')) : __('common.na'),
            'disbursed' => $this->actualDisbursedAmount($loan),
            'paid' => (float) ($payment?->amount_paid ?? 0),
            'outstanding' => $this->outstandingAmount($loan, $payment),
            'date' => $ref?->translatedFormat('d M Y'),
        ];
    }

    protected function actualDisbursedAmount(Loan $loan): float
    {
        return max(0.0, (float) ($loan->disbursed_amount ?? 0));
    }

    protected function paymentLedger(Loan $loan): ?LoanPayment
    {
        if ($loan->relationLoaded('loanPayments')) {
            return $loan->loanPayments->sortBy('id')->first();
        }

        return $loan->loanPayments()->orderBy('id')->first();
    }

    protected function outstandingAmount(Loan $loan, ?LoanPayment $payment): float
    {
        if ($payment) {
            return max(0.0, (float) ($payment->outstanding_debt ?? 0));
        }

        return $this->actualDisbursedAmount($loan);
    }
}
