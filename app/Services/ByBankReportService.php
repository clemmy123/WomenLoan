<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Scopes\ApprovalLevelScope;
use App\Support\FiscalYear;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ByBankReportService
{
    public const PERIODS = [
        'daily',
        'weekly',
        'monthly',
        'quarterly',
        'annually',
    ];

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
        $fiscalYear = FiscalYear::normalize($input['fiscal_year'] ?? null);

        $period = $input['period'] ?? 'annually';
        if (! in_array($period, self::PERIODS, true)) {
            $period = 'annually';
        }

        $sort = $input['sort'] ?? 'newest';
        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'newest';
        }

        $bankName = is_string($input['bank_name'] ?? null) ? trim($input['bank_name']) : '';
        if ($bankName === '' || ! in_array($bankName, $this->banks(), true)) {
            $bankName = null;
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

        return $this->geo->clampGeoFilters([
            'fiscal_year' => $fiscalYear,
            'period' => $period,
            'date_from' => $from,
            'date_to' => $to,
            'bank_name' => $bankName,
            'region_id' => null,
            'district_id' => null,
            'council_id' => null,
            'ward_id' => null,
            'street_id' => null,
            'sort' => $sort,
            'use_custom_dates' => $useCustomDates ? '1' : null,
        ]);
    }

    /**
     * @return list<string>
     */
    public function banks(): array
    {
        return array_values(config('banks.names', []));
    }

    public function fiscalYearOptions(?Carbon $asOf = null): array
    {
        $options = FiscalYear::options($asOf, includeAll: true);

        if (isset($options[FiscalYear::ALL_KEY])) {
            $options[FiscalYear::ALL_KEY] = __('reports.all_years');
        }

        return $options;
    }

    public function sortOptions(): array
    {
        return collect(self::SORTS)
            ->mapWithKeys(fn (string $sort) => [$sort => __('by_bank_reports.sort_'.$sort)])
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
        return 'wdf-by-bank-report-'.now()->format('Y-m-d-His').'.'.$extension;
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

        if (! empty($filters['bank_name'])) {
            $query->where('bank_name', $filters['bank_name']);
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
            'bank' => $loan->bank_name ?: __('common.na'),
            'region' => $loan->businessDetails?->region?->name ?? __('reports.unknown_region'),
            'disbursed' => $this->actualDisbursedAmount($loan),
            'paid' => (float) ($payment?->amount_paid ?? 0),
            'outstanding' => $this->outstandingAmount($loan, $payment),
            'date' => ($loan->date_issued ?? $loan->updated_at)?->translatedFormat('d M Y'),
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
