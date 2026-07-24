<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Loan;
use App\Models\LoanGroupMember;
use App\Models\LoanPayment;
use App\Models\Scopes\ApprovalLevelScope;
use App\Support\AgeCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ByAgeReportService
{
    public const SORTS = [
        'newest',
        'oldest',
        'name_asc',
        'age_asc',
        'age_desc',
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

        $ageMin = $this->normalizeAge($input['age_min'] ?? null);
        $ageMax = $this->normalizeAge($input['age_max'] ?? null);

        if ($ageMin !== null && $ageMax !== null && $ageMin > $ageMax) {
            [$ageMin, $ageMax] = [$ageMax, $ageMin];
        }

        return $this->geo->clampGeoFilters([
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'region_id' => filled($input['region_id'] ?? null) ? $input['region_id'] : null,
            'district_id' => filled($input['district_id'] ?? null) ? $input['district_id'] : null,
            'council_id' => filled($input['council_id'] ?? null) ? $input['council_id'] : null,
            'ward_id' => filled($input['ward_id'] ?? null) ? $input['ward_id'] : null,
            'street_id' => filled($input['street_id'] ?? null) ? $input['street_id'] : null,
            'sort' => $sort,
        ]);
    }

    public function regions()
    {
        return $this->geo->regionsForUser();
    }

    public function sortOptions(): array
    {
        return collect(self::SORTS)
            ->mapWithKeys(fn (string $sort) => [$sort => __('by_age_reports.sort_'.$sort)])
            ->all();
    }

    public function summary(array $filters): array
    {
        $people = $this->peopleRows($filters);

        $loanIds = $people->pluck('loan_id')->unique()->filter()->values();
        $loans = $this->loansForIds($loanIds);

        $disbursed = 0.0;
        $paid = 0.0;
        $outstanding = 0.0;

        foreach ($loans as $loan) {
            $payment = $this->paymentLedger($loan);
            $disbursed += $this->actualDisbursedAmount($loan);
            $paid += (float) ($payment?->amount_paid ?? 0);
            $outstanding += $this->outstandingAmount($loan, $payment);
        }

        $individuals = $people->where('loan_type', 'individual');
        $groupPeople = $people->where('loan_type', 'group');

        return [
            'count' => $people->count(),
            'individual_count' => $individuals->count(),
            'group_count' => $groupPeople->pluck('loan_id')->unique()->count(),
            'group_members_count' => $groupPeople->count(),
            'total_disbursed' => round($disbursed, 2),
            'total_paid' => round($paid, 2),
            'total_outstanding' => round($outstanding, 2),
        ];
    }

    public function paginatedRows(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $people = $this->peopleRows($filters)->values();
        $page = max(1, (int) request()->input('page', 1));
        $slice = $people->slice(($page - 1) * $perPage, $perPage)->values();

        return (new Paginator(
            $slice,
            $people->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        ))->withQueryString();
    }

    public function allRows(array $filters): Collection
    {
        return $this->peopleRows($filters)->values();
    }

    public function exportFilename(string $extension): string
    {
        return 'wdf-by-age-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    /**
     * One row per person: individual applicants + every group member.
     */
    protected function peopleRows(array $filters): Collection
    {
        $loans = $this->baseQuery($filters)
            ->with([
                'applicant:id,full_name,phone,dob',
                'group:id,name,phone',
                'group.members',
                'group.applicants:id,full_name,phone,dob',
                'businessDetails.region:id,name',
                'loanPayments',
            ])
            ->get();

        $people = collect();
        $ageMin = $filters['age_min'];
        $ageMax = $filters['age_max'];
        $hasAgeFilter = $ageMin !== null || $ageMax !== null;

        foreach ($loans as $loan) {
            $loanPeople = $this->peopleForLoan($loan);
            $memberCount = max(1, count($loanPeople));

            foreach ($loanPeople as $person) {
                if ($hasAgeFilter && ! AgeCalculator::matchesRange(
                    $person['dob'],
                    $ageMin,
                    $ageMax,
                )) {
                    continue;
                }

                $people->push($this->mapPersonRow($loan, $person, $memberCount));
            }
        }

        return $this->sortPeople($people, $filters['sort'] ?? 'newest');
    }

    /**
     * @return list<array{name: string, phone: ?string, dob: mixed, source: string}>
     */
    protected function peopleForLoan(Loan $loan): array
    {
        if ($loan->loan_type !== 'group') {
            if (! $loan->applicant) {
                return [];
            }

            return [[
                'name' => $loan->applicant->full_name ?: __('common.na'),
                'phone' => $loan->applicant->phone,
                'dob' => $loan->applicant->dob,
                'source' => 'individual',
            ]];
        }

        $members = $loan->group?->members;

        if ($members && $members->isNotEmpty()) {
            return $members->map(fn (LoanGroupMember $member) => [
                'name' => $member->full_name ?: __('common.na'),
                'phone' => $member->phone ?: $loan->group?->phone,
                'dob' => $member->dob,
                'source' => 'group_member',
            ])->all();
        }

        $applicants = $loan->group?->applicants;

        if ($applicants && $applicants->isNotEmpty()) {
            return $applicants->map(fn (Applicant $applicant) => [
                'name' => $applicant->full_name ?: __('common.na'),
                'phone' => $applicant->phone ?: $loan->group?->phone,
                'dob' => $applicant->dob,
                'source' => 'group_member',
            ])->all();
        }

        if ($loan->applicant) {
            return [[
                'name' => $loan->applicant->full_name ?: __('common.na'),
                'phone' => $loan->applicant->phone ?: $loan->group?->phone,
                'dob' => $loan->applicant->dob,
                'source' => 'group_member',
            ]];
        }

        return [];
    }

    protected function mapPersonRow(Loan $loan, array $person, int $memberCount = 1): array
    {
        $payment = $this->paymentLedger($loan);
        $isGroup = $loan->loan_type === 'group';
        $age = AgeCalculator::years($person['dob'] ?? null);
        $memberCount = max(1, $memberCount);
        $disbursed = $this->actualDisbursedAmount($loan);
        $paid = (float) ($payment?->amount_paid ?? 0);
        $outstanding = $this->outstandingAmount($loan, $payment);

        return [
            'loan_id' => $loan->id,
            'track_id' => $loan->loan_track_id,
            'hashid' => $loan->hashid,
            'name' => $person['name'],
            'loan_type' => $loan->loan_type,
            'loan_type_label' => $isGroup
                ? __('by_age_reports.membership_group', [
                    'group' => $loan->group?->name ?? __('common.na'),
                ])
                : __('by_age_reports.membership_individual'),
            'phone' => $person['phone'] ?: __('common.na'),
            'region' => $loan->businessDetails?->region?->name ?? __('reports.unknown_region'),
            'age' => $age,
            // Equal share so person rows do not inflate totals when summed.
            'disbursed' => round($disbursed / $memberCount, 2),
            'paid' => round($paid / $memberCount, 2),
            'outstanding' => round($outstanding / $memberCount, 2),
            'date' => ($loan->date_issued ?? $loan->updated_at)?->translatedFormat('d M Y'),
            'date_sort' => ($loan->date_issued ?? $loan->updated_at)?->timestamp ?? 0,
        ];
    }

    protected function sortPeople(Collection $people, string $sort): Collection
    {
        return match ($sort) {
            'oldest' => $people->sortBy([
                ['date_sort', 'asc'],
                ['name', 'asc'],
            ])->values(),
            'name_asc' => $people->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'age_asc' => $people->sortBy([
                fn ($row) => $row['age'] ?? PHP_INT_MAX,
                ['name', 'asc'],
            ])->values(),
            'age_desc' => $people->sortBy([
                fn ($row) => -1 * ($row['age'] ?? -1),
                ['name', 'asc'],
            ])->values(),
            'disbursed_desc' => $people->sortByDesc('disbursed')->values(),
            'paid_desc' => $people->sortByDesc('paid')->values(),
            'outstanding_desc' => $people->sortByDesc('outstanding')->values(),
            default => $people->sortBy([
                ['date_sort', 'desc'],
                ['name', 'asc'],
            ])->values(),
        };
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = $this->scopedLoanQuery()
            ->where('status', 'disbursed')
            ->where('disbursed_amount', '>', 0);

        if (! empty($filters['region_id'])) {
            $query->whereHas('businessDetails', function (Builder $q) use ($filters) {
                $q->where('region_id', $filters['region_id']);

                if (! empty($filters['district_id'])) {
                    $q->where('district_id', $filters['district_id']);
                }

                if (! empty($filters['council_id'])) {
                    $q->where('council_id', $filters['council_id']);
                }

                if (! empty($filters['ward_id'])) {
                    $q->where('ward_id', $filters['ward_id']);
                }

                if (! empty($filters['street_id'])) {
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

    protected function loansForIds(Collection $loanIds): Collection
    {
        if ($loanIds->isEmpty()) {
            return collect();
        }

        return Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->with('loanPayments')
            ->whereIn('id', $loanIds->all())
            ->get()
            ->keyBy('id');
    }

    protected function normalizeAge(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $age = (int) $value;

        if ($age < 0 || $age > 120) {
            return null;
        }

        return $age;
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
