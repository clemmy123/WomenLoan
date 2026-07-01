<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReportService
{
    public function __construct(private GeoHierarchyService $geo) {}

    public function normalizeFilters(array $input): array
    {
        $filters = [
            'period' => $input['period'] ?? 'monthly',
            'date_from' => $input['date_from'] ?? null,
            'date_to' => $input['date_to'] ?? null,
            'region_id' => $input['region_id'] ?? null,
            'district_id' => $input['district_id'] ?? null,
            'council_id' => $input['council_id'] ?? null,
            'ward_id' => $input['ward_id'] ?? null,
            'street_id' => $input['street_id'] ?? null,
            'loan_type' => $input['loan_type'] ?? null,
            'age_min' => $input['age_min'] ?? null,
            'age_max' => $input['age_max'] ?? null,
            'has_disability' => $input['has_disability'] ?? null,
            'is_widowed' => $input['is_widowed'] ?? null,
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
            $payment = $loan->loanPayments->first();
            $disbursed += (float) ($payment?->amount_disbursed ?? $loan->disbursed_amount ?? 0);
            $paid += (float) ($payment?->amount_paid ?? 0);
            $outstanding += (float) ($payment?->outstanding_debt ?? $loan->disbursed_amount ?? 0);
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
                'applicant:id,dob',
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
            'widowed' => $this->booleanBreakdown($loans, 'is_widowed', __('reports.widowed'), __('reports.not_widowed')),
            'age_buckets' => $this->ageBuckets($loans),
        ];
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = $this->scopedLoanQuery()
            ->with([
                'applicant:id,full_name,first_name,last_name,dob',
                'businessDetails.region:id,name',
                'businessDetails.district:id,name',
                'businessDetails.ward:id,name',
                'businessDetails.street:id,name',
                'loanPayments',
            ])
            ->where(function (Builder $q) {
                $q->where('status', 'disbursed')
                    ->orWhere('disbursed_amount', '>', 0);
            });

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

        if ($filters['is_widowed'] !== null && $filters['is_widowed'] !== '') {
            $query->where('is_widowed', (bool) (int) $filters['is_widowed']);
        }

        if ($filters['age_min'] || $filters['age_max']) {
            $query->whereHas('applicant', function (Builder $q) use ($filters) {
                if ($filters['age_min']) {
                    $q->whereDate('dob', '<=', now()->subYears((int) $filters['age_min']));
                }
                if ($filters['age_max']) {
                    $q->whereDate('dob', '>', now()->subYears((int) $filters['age_max'] + 1));
                }
            });
        }

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
            'name' => $loan->applicant?->full_name ?? __('common.na'),
            'region' => $loan->businessDetails?->region?->name,
            'loan_type' => loan_type_label($loan->loan_type),
            'disbursed' => (float) ($payment?->amount_disbursed ?? $loan->disbursed_amount ?? 0),
            'paid' => (float) ($payment?->amount_paid ?? 0),
            'outstanding' => (float) ($payment?->outstanding_debt ?? $loan->disbursed_amount ?? 0),
            'date' => ($loan->date_issued ?? $loan->updated_at)?->translatedFormat('d M Y'),
        ];
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
            $payment = $loan->loanPayments->first();
            $buckets[$key]['disbursed'] += (float) ($payment?->amount_disbursed ?? $loan->disbursed_amount ?? 0);
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
            $payment = $loan->loanPayments->first();
            $totals[$region] = ($totals[$region] ?? 0) + (float) ($payment?->outstanding_debt ?? $loan->disbursed_amount ?? 0);
        }

        arsort($totals);

        return [
            'labels' => array_keys($totals),
            'data' => array_values($totals),
        ];
    }

    protected function loanTypeBreakdown(Collection $loans): array
    {
        $counts = $loans->groupBy('loan_type')->map->count();

        return [
            'labels' => $counts->keys()->map(fn ($t) => loan_type_label($t))->values()->all(),
            'data' => $counts->values()->map(fn ($v) => (int) $v)->all(),
        ];
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
            $age = $loan->applicant?->dob?->age;
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
        return $this->geo->regions();
    }
}
