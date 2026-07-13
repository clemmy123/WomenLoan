<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Support\FiscalYear;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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

    public const PERIODS = [
        'daily',
        'weekly',
        'monthly',
        'quarterly',
        'annually',
    ];

    public function normalizeFilters(array $input): array
    {
        $fiscalYear = FiscalYear::normalize($input['fiscal_year'] ?? null);

        $period = $input['period'] ?? 'annually';
        if (! in_array($period, self::PERIODS, true)) {
            $period = 'annually';
        }

        $customFrom = $input['date_from'] ?? null;
        $customTo = $input['date_to'] ?? null;
        $useCustomDates = filled($customFrom)
            && filled($customTo)
            && ($input['use_custom_dates'] ?? null) === '1';

        [$from, $to] = FiscalYear::resolveFilterDates(
            $fiscalYear,
            $period,
            is_string($customFrom) ? $customFrom : null,
            is_string($customTo) ? $customTo : null,
            $useCustomDates,
        );

        return [
            'fiscal_year' => $fiscalYear,
            'period' => $period,
            'date_from' => $from,
            'date_to' => $to,
            'status' => $input['status'] ?? null,
            'use_custom_dates' => $useCustomDates ? '1' : null,
        ];
    }

    public function fiscalYearOptions(?Carbon $asOf = null): array
    {
        $options = FiscalYear::options($asOf, includeAll: true);
        if (isset($options[FiscalYear::ALL_KEY])) {
            $options[FiscalYear::ALL_KEY] = __('reports.all_years');
        }

        return $options;
    }

    public function currentFiscalYearKey(?Carbon $asOf = null): string
    {
        return FiscalYear::currentKey($asOf);
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

    public function allRows(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(fn (Loan $loan) => $this->mapRow($loan));
    }

    public function exportFilename(string $extension): string
    {
        return 'wdf-application-reports-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = $this->scopedLoanQuery()
            ->with([
                'applicant:id,full_name',
                'group:id,name',
                'group.members:id,loan_group_id,full_name',
                'group.applicants:id,full_name',
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
        } elseif ($user?->hasRole(['cdo_ward', 'cdo_council', 'cdo_region'])) {
            app(CdoLoanScopeService::class)->applyBusinessDetailsScope($query, $user);
        }

        return $query;
    }

    protected function mapRow(Loan $loan): array
    {
        $payment = $loan->relationLoaded('loanPayments')
            ? $loan->loanPayments->sortBy('id')->first()
            : $loan->loanPayments()->orderBy('id')->first();

        $isGroup = $loan->loan_type === 'group';
        $members = $isGroup ? $this->groupMemberNames($loan) : [];

        return [
            'track_id' => $loan->loan_track_id,
            'hashid' => $loan->hashid,
            'loan_type' => $loan->loan_type,
            'full_name' => $isGroup
                ? ($loan->group?->name ?? __('common.na'))
                : ($loan->applicant?->full_name ?? __('common.na')),
            'members' => $members,
            'amount_requested' => (float) ($loan->requested_amount ?? 0),
            'amount_disbursed' => max(0.0, (float) ($loan->disbursed_amount ?? 0)),
            'outstanding' => (float) ($payment?->outstanding_debt ?? 0),
            'amount_repaid' => (float) ($payment?->amount_paid ?? 0),
        ];
    }

    /**
     * @return list<string>
     */
    protected function groupMemberNames(Loan $loan): array
    {
        $members = $loan->group?->members;

        if ($members && $members->isNotEmpty()) {
            return $members
                ->pluck('full_name')
                ->filter()
                ->values()
                ->all();
        }

        $applicants = $loan->group?->applicants;

        if ($applicants && $applicants->isNotEmpty()) {
            return $applicants
                ->pluck('full_name')
                ->filter()
                ->values()
                ->all();
        }

        $fallback = $loan->applicant?->full_name;

        return $fallback ? [$fallback] : [];
    }
}
