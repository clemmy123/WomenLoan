<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Loan;
use App\Models\LoanGroupMember;
use App\Models\Scopes\ApprovalLevelScope;
use App\Support\FiscalYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class GeneralReportService
{
    public const PERIODS = [
        'monthly',
        'quarterly',
        'annually',
    ];

    public function normalizeFilters(array $input): array
    {
        $loanType = $input['loan_type'] ?? null;
        if (! in_array($loanType, Applicant::LOAN_TYPES, true)) {
            $loanType = null;
        }

        $period = $input['period'] ?? 'annually';
        if (! in_array($period, self::PERIODS, true)) {
            $period = 'annually';
        }

        [$from, $to] = FiscalYear::resolveFilterDates(
            FiscalYear::currentKey(),
            $period,
        );

        return [
            'loan_type' => $loanType,
            'period' => $period,
            'date_from' => $from,
            'date_to' => $to,
        ];
    }

    public function summary(array $filters): array
    {
        $loans = $this->baseQuery($filters)->get();
        $individual = $loans->where('loan_type', 'individual');
        $groupLoans = $this->uniqueGroupLoans($loans->where('loan_type', 'group'));

        $received = 0;
        $processing = 0;
        $missed = 0;

        foreach ($individual as $loan) {
            $this->addWomenToOutcome($loan, 1, $received, $processing, $missed);
        }

        $groupMembers = 0;
        foreach ($groupLoans as $loan) {
            $people = count($this->groupPeople($loan));
            $groupMembers += $people;
            $this->addWomenToOutcome($loan, $people, $received, $processing, $missed);
        }

        $womenCount = $received + $processing + $missed;

        return [
            'women_count' => $womenCount,
            'individual_count' => $individual->count(),
            'group_count' => $groupLoans->count(),
            'group_members_count' => $groupMembers,
            'loan_count' => $loans->count(),
            'applied' => $womenCount,
            'received' => $received,
            'processing' => $processing,
            'missed' => $missed,
        ];
    }

    protected function addWomenToOutcome(Loan $loan, int $people, int &$received, int &$processing, int &$missed): void
    {
        if ($people < 1) {
            return;
        }

        match ($this->outcomeBucket((string) $loan->status)) {
            'received' => $received += $people,
            'missed' => $missed += $people,
            default => $processing += $people,
        };
    }

    protected function outcomeBucket(string $status): string
    {
        if (in_array($status, ApplicationReportService::RECEIVED_STATUSES, true)) {
            return 'received';
        }

        if (in_array($status, ApplicationReportService::REJECTED_STATUSES, true)) {
            return 'missed';
        }

        return 'processing';
    }

    public function chartPayload(array $filters): array
    {
        $summary = $this->summary($filters);
        $applied = (int) $summary['applied'];
        $received = (int) $summary['received'];
        $processing = (int) $summary['processing'];
        $missed = (int) $summary['missed'];

        return [
            'outcome_pie' => [
                'labels' => [
                    $this->chartLabel('bucket_received', $received),
                    $this->chartLabel('bucket_processing', $processing),
                    $this->chartLabel('bucket_missed', $missed),
                ],
                'data' => [$received, $processing, $missed],
            ],
            'outcome_bars' => [
                'labels' => [
                    __('general_reports.bucket_applied'),
                    __('general_reports.bucket_received'),
                    __('general_reports.bucket_processing'),
                    __('general_reports.bucket_missed'),
                ],
                'data' => [$applied, $received, $processing, $missed],
            ],
        ];
    }

    protected function chartLabel(string $key, int $count): string
    {
        return __('general_reports.'.$key).' ('.number_format($count).')';
    }

    public function paginatedRows(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $rows = $this->allRows($filters);
        $page = max(1, (int) request()->input('page', 1));
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return (new Paginator(
            $slice,
            $rows->count(),
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
        $loans = $this->baseQuery($filters)->orderBy('id')->get();
        $loanType = $filters['loan_type'] ?? null;

        if ($loanType === 'group') {
            return $this->uniqueGroupLoans($loans)
                ->map(fn (Loan $loan) => $this->mapGroupRow($loan))
                ->sortBy('group_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        }

        if ($loanType === 'individual') {
            return $loans
                ->map(fn (Loan $loan) => $this->mapIndividualRow($loan))
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        }

        $people = collect();
        $seenGroups = [];

        foreach ($loans as $loan) {
            if ($loan->loan_type === 'group') {
                $groupKey = $loan->loan_group_id ?? 'loan-'.$loan->id;
                if (isset($seenGroups[$groupKey])) {
                    continue;
                }
                $seenGroups[$groupKey] = true;

                foreach ($this->groupPeople($loan) as $person) {
                    $people->push($this->mapAllRow($loan, $person['name'], 'group'));
                }

                continue;
            }

            $people->push($this->mapAllRow(
                $loan,
                $loan->applicant?->full_name ?: __('common.na'),
                'individual',
            ));
        }

        return $people->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    public function exportFilename(string $extension): string
    {
        return 'wdf-registered-women-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    public function viewMode(array $filters): string
    {
        return $filters['loan_type'] ?? 'all';
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = $this->scopedLoanQuery()
            ->with([
                'applicant:id,full_name',
                'group:id,name',
                'group.members:id,loan_group_id,full_name',
                'group.applicants:id,full_name',
            ]);

        if (! empty($filters['loan_type'])) {
            $query->where('loan_type', $filters['loan_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
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
            // No geo filters in the UI — ward / council / region staff still only see their zone.
            app(CdoLoanScopeService::class)->applyBusinessDetailsScope($query, $user);
        }

        return $query;
    }

    protected function uniqueGroupLoans(Collection $loans): Collection
    {
        return $loans
            ->unique(fn (Loan $loan) => $loan->loan_group_id ?? 'loan-'.$loan->id)
            ->values();
    }

    /**
     * @return list<array{name: string}>
     */
    protected function groupPeople(Loan $loan): array
    {
        $members = $loan->group?->members;

        if ($members && $members->isNotEmpty()) {
            return $members
                ->map(fn (LoanGroupMember $member) => [
                    'name' => $member->full_name ?: __('common.na'),
                ])
                ->all();
        }

        $applicants = $loan->group?->applicants;

        if ($applicants && $applicants->isNotEmpty()) {
            return $applicants
                ->map(fn (Applicant $applicant) => [
                    'name' => $applicant->full_name ?: __('common.na'),
                ])
                ->all();
        }

        if ($loan->applicant) {
            return [[
                'name' => $loan->applicant->full_name ?: __('common.na'),
            ]];
        }

        return [];
    }

    protected function mapAllRow(Loan $loan, string $name, string $loanType): array
    {
        return [
            'view' => 'all',
            'name' => $name,
            'loan_type' => $loanType,
            'loan_type_label' => loan_type_label($loanType),
            'account_number' => $this->accountNumber($loan),
            'group_name' => $loanType === 'group'
                ? ($loan->group?->name ?? __('common.na'))
                : null,
            'hashid' => $loan->hashid,
            'track_id' => $loan->loan_track_id,
        ];
    }

    protected function mapIndividualRow(Loan $loan): array
    {
        return [
            'view' => 'individual',
            'name' => $loan->applicant?->full_name ?: __('common.na'),
            'account_number' => $this->accountNumber($loan),
            'hashid' => $loan->hashid,
            'track_id' => $loan->loan_track_id,
        ];
    }

    protected function mapGroupRow(Loan $loan): array
    {
        $members = collect($this->groupPeople($loan))->pluck('name')->filter()->values()->all();

        return [
            'view' => 'group',
            'group_name' => $loan->group?->name ?? __('common.na'),
            'members' => $members,
            'members_label' => $members !== [] ? implode(', ', $members) : __('common.na'),
            'account_number' => $this->accountNumber($loan),
            'hashid' => $loan->hashid,
            'track_id' => $loan->loan_track_id,
        ];
    }

    protected function accountNumber(Loan $loan): string
    {
        return filled($loan->bank_number) ? (string) $loan->bank_number : __('common.na');
    }
}
