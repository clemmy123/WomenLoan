<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ApplicationReportService
{
    public const STATUSES = [
        'pending',
        'received',
        'in_review',
        'awaiting_applicant',
        'declined_by_applicant',
        'approved',
        'ready_for_disbursement',
        'disbursed',
        'rejected',
    ];

    public function normalizeFilters(array $input): array
    {
        $filters = [
            'period' => $input['period'] ?? 'monthly',
            'date_from' => $input['date_from'] ?? null,
            'date_to' => $input['date_to'] ?? null,
            'status' => $input['status'] ?? null,
        ];

        if (! $filters['date_from'] || ! $filters['date_to']) {
            $filters['date_to'] = now()->toDateString();
            $filters['date_from'] = match ($filters['period']) {
                'daily' => now()->subDays(30)->toDateString(),
                'weekly' => now()->subWeeks(12)->toDateString(),
                'quarterly' => now()->subQuarters(8)->toDateString(),
                'annually' => now()->subYears(5)->toDateString(),
                default => now()->subMonths(12)->toDateString(),
            };
        }

        return $filters;
    }

    public function paginatedRows(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Loan $loan) => $this->mapRow($loan));
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = $this->scopedLoanQuery()
            ->with([
                'applicant:id,full_name',
                'loanPayments',
            ]);

        if ($filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    protected function scopedLoanQuery(): Builder
    {
        $user = Auth::user();
        $query = Loan::withoutGlobalScope(ApprovalLevelScope::class);

        if ($user?->hasRole('applicant')) {
            $query->where('user_id', $user->id);
        } elseif ($user?->hasRole('cdo_ward')) {
            $query->whereHas('businessDetails', fn (Builder $q) => $q->where('ward_id', $user->zoneable_id));
        } elseif ($user?->hasRole('cdo_council')) {
            $query->whereHas('businessDetails', fn (Builder $q) => $q->where('council_id', $user->zoneable_id));
        } elseif ($user?->hasRole('cdo_region')) {
            $query->whereHas('businessDetails', fn (Builder $q) => $q->where('region_id', $user->zoneable_id));
        }

        return $query;
    }

    protected function mapRow(Loan $loan): array
    {
        $payment = $loan->loanPayments->first();

        return [
            'track_id' => $loan->loan_track_id,
            'hashid' => $loan->hashid,
            'full_name' => $loan->applicant?->full_name ?? __('common.na'),
            'amount_requested' => (float) ($loan->requested_amount ?? 0),
            'amount_disbursed' => (float) ($payment?->amount_disbursed ?? $loan->disbursed_amount ?? 0),
            'bank_name' => $loan->bank_name ?? __('common.na'),
            'outstanding' => (float) ($payment?->outstanding_debt ?? 0),
            'amount_repaid' => (float) ($payment?->amount_paid ?? 0),
        ];
    }
}
