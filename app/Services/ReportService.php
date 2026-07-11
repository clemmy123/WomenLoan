<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Support\AgeCalculator;
use App\Support\FiscalYear;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReportService
{
    public const PERIODS = [
        'daily',
        'weekly',
        'monthly',
        'quarterly',
        'annually',
    ];

    public function __construct(private GeoHierarchyService $geo) {}

    public function normalizeFilters(array $input): array
    {
        $loanType = $input['loan_type'] ?? null;
        if (! in_array($loanType, Applicant::LOAN_TYPES, true)) {
            $loanType = null;
        }

        $maritalStatus = $input['marital_status'] ?? null;
        if (! in_array($maritalStatus, Applicant::MARITAL_STATUSES, true)) {
            $maritalStatus = null;
        }

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

        return $this->geo->clampGeoFilters([
            'fiscal_year' => $fiscalYear,
            'period' => $period,
            'date_from' => $from,
            'date_to' => $to,
            'region_id' => $input['region_id'] ?? null,
            'district_id' => $input['district_id'] ?? null,
            'council_id' => $input['council_id'] ?? null,
            'ward_id' => $input['ward_id'] ?? null,
            'street_id' => $input['street_id'] ?? null,
            'loan_type' => $loanType,
            'age_min' => $input['age_min'] ?? null,
            'age_max' => $input['age_max'] ?? null,
            'has_disability' => $input['has_disability'] ?? null,
            'marital_status' => $maritalStatus,
            'use_custom_dates' => $useCustomDates ? '1' : null,
        ]);
    }

    public function fiscalYearOptions(?Carbon $asOf = null): array
    {
        return $this->labelFiscalYearOptions(FiscalYear::options($asOf, includeAll: true));
    }

    public function currentFiscalYearKey(?Carbon $asOf = null): string
    {
        return FiscalYear::currentKey($asOf);
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, string>
     */
    protected function labelFiscalYearOptions(array $options): array
    {
        if (isset($options[FiscalYear::ALL_KEY])) {
            $options[FiscalYear::ALL_KEY] = __('reports.all_years');
        }

        return $options;
    }

    public function paginatedRows(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest('date_issued')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Loan $loan) => $this->mapRow($loan));
    }

    public function allRows(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->latest('date_issued')
            ->latest('id')
            ->get()
            ->map(fn (Loan $loan) => $this->mapRow($loan));
    }

    public function exportFilename(string $extension): string
    {
        return 'wdf-reports-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    public function summary(array $filters): array
    {
        $loans = $this->baseQuery($filters)
            ->with('loanPayments')
            ->get();

        $disbursed = 0.0;
        $paid = 0.0;
        $outstanding = 0.0;

        foreach ($loans as $loan) {
            $payment = $this->paymentLedger($loan);
            $disbursed += $this->actualDisbursedAmount($loan);
            $paid += (float) ($payment?->amount_paid ?? 0);
            $outstanding += $this->outstandingAmount($loan, $payment);
        }

        return [
            'count' => $loans->count(),
            'total_disbursed' => $disbursed,
            'total_paid' => $paid,
            'total_outstanding' => $outstanding,
        ];
    }

    public function chartData(array $filters): array
    {
        $loans = $this->baseQuery($filters)
            ->with([
                'loanPayments',
                'applicant:id,dob,marital_status',
                'businessDetails.region:id,name',
            ])
            ->get();

        return [
            'financial_trend' => array_merge($this->financialTrend($loans, $filters['period']), [
                'legend_disbursed' => __('reports.legend_disbursed'),
                'legend_paid' => __('reports.legend_paid'),
            ]),
            'by_region' => $this->amountByRegion($loans),
            'loan_type' => $this->loanTypeBreakdown($loans),
            'disability' => $this->booleanBreakdown($loans, 'has_disability', __('reports.with_disability'), __('reports.without_disability')),
            'marital_status' => $this->maritalStatusBreakdown($loans),
            'age_buckets' => $this->ageBuckets($loans),
        ];
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = $this->scopedLoanQuery()
            ->with([
                'applicant:id,full_name,first_name,last_name,dob,marital_status',
                'businessDetails.region:id,name',
                'businessDetails.district:id,name',
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

        if ($filters['loan_type']) {
            $query->where('loan_type', $filters['loan_type']);
        }

        if ($filters['has_disability'] !== null && $filters['has_disability'] !== '') {
            $query->where('has_disability', (bool) (int) $filters['has_disability']);
        }

        if ($filters['marital_status'] || $filters['age_min'] || $filters['age_max']) {
            $query->whereHas('applicant', function (Builder $q) use ($filters) {
                if ($filters['marital_status']) {
                    $q->where('marital_status', $filters['marital_status']);
                }

                // Birthday-aware bounds: age grows only on/after the birthday date.
                if ($filters['age_min']) {
                    $q->whereDate(
                        'dob',
                        '<=',
                        AgeCalculator::latestDobForMinAge((int) $filters['age_min'])->toDateString()
                    );
                }
                if ($filters['age_max']) {
                    $q->whereDate(
                        'dob',
                        '>',
                        AgeCalculator::earliestExclusiveDobForMaxAge((int) $filters['age_max'])->toDateString()
                    );
                }
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

    protected function mapRow(Loan $loan): array
    {
        $payment = $this->paymentLedger($loan);

        return [
            'track_id' => $loan->loan_track_id,
            'hashid' => $loan->hashid,
            'name' => $loan->applicant?->full_name ?? __('common.na'),
            'region' => $loan->businessDetails?->region?->name,
            'loan_type' => loan_type_label($loan->loan_type),
            'disbursed' => $this->actualDisbursedAmount($loan),
            'paid' => (float) ($payment?->amount_paid ?? 0),
            'outstanding' => $this->outstandingAmount($loan, $payment),
            'date' => ($loan->date_issued ?? $loan->updated_at)?->translatedFormat('d M Y'),
        ];
    }

    /**
     * Canonical disbursed principal is loans.disbursed_amount (set at workflow disburse).
     */
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

    protected function loanReferenceDate(Loan $loan): Carbon
    {
        return Carbon::parse($loan->date_issued ?? $loan->updated_at);
    }

    protected function financialTrend(Collection $loans, string $period): array
    {
        $buckets = [];

        foreach ($loans as $loan) {
            $key = $this->periodKey($this->loanReferenceDate($loan), $period);
            $buckets[$key] ??= ['disbursed' => 0.0, 'paid' => 0.0];
            $payment = $this->paymentLedger($loan);
            $buckets[$key]['disbursed'] += $this->actualDisbursedAmount($loan);
            $buckets[$key]['paid'] += (float) ($payment?->amount_paid ?? 0);
        }

        ksort($buckets);

        return [
            'labels' => array_map(fn ($k) => $this->periodLabel($k, $period), array_keys($buckets)),
            'disbursed' => array_column($buckets, 'disbursed'),
            'paid' => array_column($buckets, 'paid'),
        ];
    }

    protected function amountByRegion(Collection $loans): array
    {
        $totals = [];

        foreach ($loans as $loan) {
            $region = $loan->businessDetails?->region?->name ?? __('reports.unknown_region');
            $payment = $this->paymentLedger($loan);
            $totals[$region] = ($totals[$region] ?? 0) + $this->outstandingAmount($loan, $payment);
        }

        arsort($totals);

        return [
            'labels' => array_keys($totals),
            'data' => array_values($totals),
        ];
    }

    protected function loanTypeBreakdown(Collection $loans): array
    {
        $labels = [];
        $data = [];

        foreach (Applicant::LOAN_TYPES as $type) {
            $labels[] = loan_type_label($type);
            $data[] = $loans->where('loan_type', $type)->count();
        }

        return compact('labels', 'data');
    }

    protected function maritalStatusBreakdown(Collection $loans): array
    {
        $labels = [];
        $data = [];

        foreach (Applicant::MARITAL_STATUSES as $status) {
            $labels[] = __('applicants.marital_statuses.'.$status);
            $data[] = $loans->filter(
                fn (Loan $loan) => ($loan->applicant?->marital_status ?? null) === $status
            )->count();
        }

        return compact('labels', 'data');
    }

    protected function booleanBreakdown(Collection $loans, string $field, string $trueLabel, string $falseLabel): array
    {
        $true = $loans->where($field, true)->count();
        $false = $loans->where($field, false)->count();

        return [
            'labels' => [$trueLabel, $falseLabel],
            'data' => [$true, $false],
        ];
    }

    protected function ageBuckets(Collection $loans): array
    {
        $labels = ['18-25', '26-35', '36-45', '46-55', '56+'];
        $data = array_fill_keys($labels, 0);

        foreach ($loans as $loan) {
            $age = AgeCalculator::years($loan->applicant?->dob);
            if ($age === null) {
                continue;
            }

            $bucket = match (true) {
                $age <= 25 => '18-25',
                $age <= 35 => '26-35',
                $age <= 45 => '36-45',
                $age <= 55 => '46-55',
                default => '56+',
            };

            $data[$bucket]++;
        }

        return [
            'labels' => array_keys($data),
            'data' => array_values($data),
        ];
    }

    protected function periodKey(Carbon $date, string $period): string
    {
        return match ($period) {
            'daily' => $date->format('Y-m-d'),
            'weekly' => $date->format('o-\WW'),
            'quarterly' => $date->format('Y').'-Q'.$date->quarter,
            'annually' => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    protected function periodLabel(string $key, string $period): string
    {
        return match ($period) {
            'daily' => Carbon::parse($key)->format('d M'),
            'weekly' => 'W '.substr($key, -2),
            'quarterly' => str_replace('-', ' ', $key),
            'annually' => $key,
            default => Carbon::createFromFormat('Y-m', $key)->format('M Y'),
        };
    }

    public function regions()
    {
        return $this->geo->regionsForUser();
    }
}
