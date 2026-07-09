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

class AnalyticalReportService
{
    public const PERIODS = [
        'daily',
        'weekly',
        'monthly',
        'semi_annual',
        'annually',
    ];

    public const QUARTERS = [
        'q1',
        'q2',
        'q3',
        'q4',
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
        [$fyFrom, $fyTo] = FiscalYear::dateRange($fiscalYear);

        $period = $input['period'] ?? 'annually';
        if (! in_array($period, self::PERIODS, true)) {
            $period = 'annually';
        }

        $quarter = $input['quarter'] ?? null;
        if (! in_array($quarter, self::QUARTERS, true)) {
            $quarter = null;
        }

        $sort = $input['sort'] ?? 'newest';
        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'newest';
        }

        // Fiscal year is the outer scope. Quarter / period / custom dates resolve inside it.
        if ($quarter) {
            [$from, $to] = FiscalYear::periodRangeWithin($quarter, $fyFrom, $fyTo);
            $useCustomDates = false;
        } else {
            [$from, $to] = FiscalYear::periodRangeWithin($period, $fyFrom, $fyTo);
            $customFrom = $input['date_from'] ?? null;
            $customTo = $input['date_to'] ?? null;
            $useCustomDates = filled($customFrom)
                && filled($customTo)
                && ($input['use_custom_dates'] ?? null) === '1';

            if ($useCustomDates) {
                [$from, $to] = FiscalYear::clampDates((string) $customFrom, (string) $customTo, $fyFrom, $fyTo);
            }
        }

        return [
            'fiscal_year' => $fiscalYear,
            'period' => $period,
            'quarter' => $quarter,
            'date_from' => $from,
            'date_to' => $to,
            'region_id' => $input['region_id'] ?? null,
            'district_id' => $input['district_id'] ?? null,
            'council_id' => $input['council_id'] ?? null,
            'ward_id' => $input['ward_id'] ?? null,
            'street_id' => $input['street_id'] ?? null,
            'sort' => $sort,
            'search' => trim((string) ($input['search'] ?? '')),
            'use_custom_dates' => $useCustomDates ? '1' : null,
        ];
    }

    public function fiscalYearOptions(?Carbon $asOf = null): array
    {
        return FiscalYear::options($asOf);
    }

    public function currentFiscalYearKey(?Carbon $asOf = null): string
    {
        return FiscalYear::currentKey($asOf);
    }

    public function summary(array $filters): array
    {
        $loans = $this->baseQuery($filters)
            ->with('loanPayments')
            ->get();

        $individual = $loans->where('loan_type', 'individual');
        $group = $loans->where('loan_type', 'group');

        return [
            'individual_count' => $individual->count(),
            'group_count' => $group->count(),
            'individual_disbursed' => $this->sumDisbursed($individual),
            'group_disbursed' => $this->sumDisbursed($group),
            'individual_paid' => $this->sumPaid($individual),
            'group_paid' => $this->sumPaid($group),
            'individual_outstanding' => $this->sumOutstanding($individual),
            'group_outstanding' => $this->sumOutstanding($group),
            'total_count' => $loans->count(),
            'total_disbursed' => $this->sumDisbursed($loans),
            'total_paid' => $this->sumPaid($loans),
            'total_outstanding' => $this->sumOutstanding($loans),
        ];
    }

    public function chartData(array $filters): array
    {
        $loans = $this->baseQuery($filters)
            ->with([
                'loanPayments',
                'businessDetails.region:id,name',
            ])
            ->get();

        return [
            'by_type_amounts' => $this->typeAmountChart($loans),
            'repayment_progress' => $this->repaymentProgressChart($loans),
            'financial_trend' => array_merge($this->financialTrend($loans, $filters['quarter'] ?? $filters['period']), [
                'legend_disbursed' => __('analytical_reports.legend_disbursed'),
                'legend_paid' => __('analytical_reports.legend_paid'),
            ]),
            'outstanding_by_region' => $this->outstandingByRegion($loans),
        ];
    }

    public function paginatedIndividuals(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters)->where('loan_type', 'individual');
        $this->applySearch($query, $filters['search'], 'individual');
        $this->applySort($query, $filters['sort'], 'individual');

        return $query
            ->paginate($perPage, ['*'], 'individual_page')
            ->withQueryString()
            ->through(fn (Loan $loan) => $this->mapIndividualRow($loan));
    }

    public function paginatedGroups(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters)->where('loan_type', 'group');
        $this->applySearch($query, $filters['search'], 'group');
        $this->applySort($query, $filters['sort'], 'group');

        return $query
            ->paginate($perPage, ['*'], 'group_page')
            ->withQueryString()
            ->through(fn (Loan $loan) => $this->mapGroupRow($loan));
    }

    public function allIndividualRows(array $filters): Collection
    {
        $query = $this->baseQuery($filters)->where('loan_type', 'individual');
        $this->applySearch($query, $filters['search'], 'individual');
        $this->applySort($query, $filters['sort'], 'individual');

        return $query->get()->map(fn (Loan $loan) => $this->mapIndividualRow($loan));
    }

    public function allGroupRows(array $filters): Collection
    {
        $query = $this->baseQuery($filters)->where('loan_type', 'group');
        $this->applySearch($query, $filters['search'], 'group');
        $this->applySort($query, $filters['sort'], 'group');

        return $query->get()->map(fn (Loan $loan) => $this->mapGroupRow($loan));
    }

    public function exportFilename(string $extension): string
    {
        return 'wdf-analytical-overview-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    public function regions()
    {
        return $this->geo->regions();
    }

    public function sortOptions(): array
    {
        return collect(self::SORTS)
            ->mapWithKeys(fn (string $sort) => [$sort => __('analytical_reports.sort_'.$sort)])
            ->all();
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = $this->scopedLoanQuery()
            ->with([
                'applicant:id,full_name,first_name,last_name,phone',
                'group:id,name,phone',
                'group.members:id,loan_group_id,full_name',
                'businessDetails.region:id,name',
                'businessDetails.district:id,name',
                'businessDetails.council:id,name',
                'businessDetails.ward:id,name',
                'businessDetails.street:id,name',
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

        $hasGeoFilter = $filters['region_id']
            || $filters['district_id']
            || $filters['council_id']
            || $filters['ward_id']
            || $filters['street_id'];

        if ($hasGeoFilter) {
            $query->whereHas('businessDetails', function (Builder $q) use ($filters) {
                if ($filters['region_id']) {
                    $q->where('region_id', $filters['region_id']);
                }
                if ($filters['district_id']) {
                    $q->where('district_id', $filters['district_id']);
                }
                if ($filters['council_id']) {
                    $q->where('council_id', $filters['council_id']);
                }
                if ($filters['ward_id']) {
                    $q->where('ward_id', $filters['ward_id']);
                }
                if ($filters['street_id']) {
                    $q->where('street_id', $filters['street_id']);
                }
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

    protected function applySearch(Builder $query, string $search, string $type): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $q) use ($like, $type) {
            $q->where('loan_track_id', 'like', $like)
                ->orWhere('bank_name', 'like', $like);

            if ($type === 'individual') {
                $q->orWhereHas('applicant', fn (Builder $a) => $a
                    ->where('full_name', 'like', $like)
                    ->orWhere('phone', 'like', $like));
            } else {
                $q->orWhereHas('group', fn (Builder $g) => $g
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like));
            }
        });
    }

    protected function applySort(Builder $query, string $sort, string $type): void
    {
        match ($sort) {
            'oldest' => $query->oldest('date_issued')->oldest('id'),
            'name_asc' => $type === 'individual'
                ? $query->leftJoin('applicants', 'loans.applicant_id', '=', 'applicants.id')
                    ->orderBy('applicants.full_name')
                    ->select('loans.*')
                : $query->leftJoin('loan_groups', 'loans.loan_group_id', '=', 'loan_groups.id')
                    ->orderBy('loan_groups.name')
                    ->select('loans.*'),
            'disbursed_desc' => $query->orderByDesc('disbursed_amount')->orderByDesc('id'),
            'paid_desc' => $query
                ->orderByDesc(
                    \App\Models\LoanPayment::select('amount_paid')
                        ->whereColumn('loan_payments.loan_id', 'loans.id')
                        ->orderBy('id')
                        ->limit(1)
                )
                ->orderByDesc('id'),
            'outstanding_desc' => $query
                ->orderByDesc(
                    \App\Models\LoanPayment::select('outstanding_debt')
                        ->whereColumn('loan_payments.loan_id', 'loans.id')
                        ->orderBy('id')
                        ->limit(1)
                )
                ->orderByDesc('id'),
            default => $query->latest('date_issued')->latest('id'),
        };
    }

    protected function mapIndividualRow(Loan $loan): array
    {
        $payment = $this->paymentLedger($loan);
        $lastPaymentDate = $this->lastPaymentDate($payment);

        return [
            'track_id' => $loan->loan_track_id,
            'hashid' => $loan->hashid,
            'name' => $loan->applicant?->full_name ?? __('common.na'),
            'bank' => $loan->bank_name ?: __('common.na'),
            'phone' => $loan->applicant?->phone ?: __('common.na'),
            'disbursed' => $this->actualDisbursedAmount($loan),
            'paid' => (float) ($payment?->amount_paid ?? 0),
            'paid_on' => $lastPaymentDate
                ? Carbon::parse($lastPaymentDate)->translatedFormat('d M Y')
                : '—',
            'outstanding' => $this->outstandingAmount($loan, $payment),
            'location' => $this->locationLabel($loan),
            'date' => ($loan->date_issued ?? $loan->updated_at)?->translatedFormat('d M Y'),
        ];
    }

    protected function mapGroupRow(Loan $loan): array
    {
        $payment = $this->paymentLedger($loan);
        $members = $loan->group?->members ?? collect();

        return [
            'track_id' => $loan->loan_track_id,
            'hashid' => $loan->hashid,
            'name' => $loan->group?->name ?? __('common.na'),
            'members_count' => $members->count(),
            'members' => $members->pluck('full_name')->filter()->values()->all(),
            'location' => $this->locationLabel($loan),
            'disbursed' => $this->actualDisbursedAmount($loan),
            'paid' => (float) ($payment?->amount_paid ?? 0),
            'outstanding' => $this->outstandingAmount($loan, $payment),
            'date' => ($loan->date_issued ?? $loan->updated_at)?->translatedFormat('d M Y'),
        ];
    }

    protected function locationLabel(Loan $loan): string
    {
        $parts = array_filter([
            $loan->businessDetails?->region?->name,
            $loan->businessDetails?->district?->name,
            $loan->businessDetails?->council?->name,
            $loan->businessDetails?->ward?->name,
            $loan->businessDetails?->street?->name,
        ]);

        return $parts ? implode(', ', $parts) : __('common.na');
    }

    protected function lastPaymentDate(?\App\Models\LoanPayment $payment): ?string
    {
        if (! $payment) {
            return null;
        }

        $transactions = $payment->payment_history['transactions'] ?? [];
        if (! is_array($transactions) || $transactions === []) {
            return null;
        }

        $dates = collect($transactions)
            ->pluck('date')
            ->filter()
            ->sort()
            ->values();

        return $dates->last();
    }

    protected function sumDisbursed(Collection $loans): float
    {
        return round($loans->sum(fn (Loan $loan) => $this->actualDisbursedAmount($loan)), 2);
    }

    protected function sumPaid(Collection $loans): float
    {
        return round($loans->sum(function (Loan $loan) {
            return (float) ($this->paymentLedger($loan)?->amount_paid ?? 0);
        }), 2);
    }

    protected function sumOutstanding(Collection $loans): float
    {
        return round($loans->sum(function (Loan $loan) {
            return $this->outstandingAmount($loan, $this->paymentLedger($loan));
        }), 2);
    }

    protected function actualDisbursedAmount(Loan $loan): float
    {
        return max(0.0, (float) ($loan->disbursed_amount ?? 0));
    }

    protected function paymentLedger(Loan $loan): ?\App\Models\LoanPayment
    {
        if ($loan->relationLoaded('loanPayments')) {
            return $loan->loanPayments->sortBy('id')->first();
        }

        return $loan->loanPayments()->orderBy('id')->first();
    }

    protected function outstandingAmount(Loan $loan, ?\App\Models\LoanPayment $payment): float
    {
        if ($payment) {
            return max(0.0, (float) ($payment->outstanding_debt ?? 0));
        }

        return $this->actualDisbursedAmount($loan);
    }

    protected function typeAmountChart(Collection $loans): array
    {
        $individual = $loans->where('loan_type', 'individual');
        $group = $loans->where('loan_type', 'group');

        return [
            'labels' => [
                loan_type_label('individual'),
                loan_type_label('group'),
            ],
            'counts' => [$individual->count(), $group->count()],
            'disbursed' => [$this->sumDisbursed($individual), $this->sumDisbursed($group)],
            'paid' => [$this->sumPaid($individual), $this->sumPaid($group)],
        ];
    }

    protected function repaymentProgressChart(Collection $loans): array
    {
        $disbursed = $this->sumDisbursed($loans);
        $paid = $this->sumPaid($loans);
        $outstanding = $this->sumOutstanding($loans);

        return [
            'labels' => [
                __('analytical_reports.legend_paid'),
                __('analytical_reports.legend_outstanding'),
            ],
            'data' => [$paid, $outstanding],
            'disbursed' => $disbursed,
        ];
    }

    protected function financialTrend(Collection $loans, string $period): array
    {
        $buckets = [];
        $chartPeriod = match ($period) {
            'daily' => 'daily',
            'weekly' => 'weekly',
            'q1', 'q2', 'q3', 'q4', 'semi_annual', 'annually' => 'monthly',
            default => 'monthly',
        };

        foreach ($loans as $loan) {
            $key = $this->periodKey($this->loanReferenceDate($loan), $chartPeriod);
            $buckets[$key] ??= ['disbursed' => 0.0, 'paid' => 0.0];
            $payment = $this->paymentLedger($loan);
            $buckets[$key]['disbursed'] += $this->actualDisbursedAmount($loan);
            $buckets[$key]['paid'] += (float) ($payment?->amount_paid ?? 0);
        }

        ksort($buckets);

        return [
            'labels' => array_map(fn ($k) => $this->periodLabel($k, $chartPeriod), array_keys($buckets)),
            'disbursed' => array_column($buckets, 'disbursed'),
            'paid' => array_column($buckets, 'paid'),
        ];
    }

    protected function outstandingByRegion(Collection $loans): array
    {
        $totals = [];

        foreach ($loans as $loan) {
            $region = $loan->businessDetails?->region?->name ?? __('reports.unknown_region');
            $totals[$region] = ($totals[$region] ?? 0) + $this->outstandingAmount($loan, $this->paymentLedger($loan));
        }

        arsort($totals);

        return [
            'labels' => array_keys($totals),
            'data' => array_values($totals),
        ];
    }

    protected function loanReferenceDate(Loan $loan): Carbon
    {
        return Carbon::parse($loan->date_issued ?? $loan->updated_at);
    }

    protected function periodKey(Carbon $date, string $period): string
    {
        return match ($period) {
            'daily' => $date->format('Y-m-d'),
            'weekly' => $date->format('o-\WW'),
            default => $date->format('Y-m'),
        };
    }

    protected function periodLabel(string $key, string $period): string
    {
        return match ($period) {
            'daily' => Carbon::parse($key)->format('d M'),
            'weekly' => 'W '.substr($key, -2),
            default => Carbon::createFromFormat('Y-m', $key)->format('M Y'),
        };
    }
}
