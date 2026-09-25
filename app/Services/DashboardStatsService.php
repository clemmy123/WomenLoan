<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\Concerns\FiltersLoanLists;
use App\Support\FiscalYear;
use App\Support\WorkflowSteps;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    use FiltersLoanLists;

    protected int $cacheTtl = 45;

    protected function loanQueryForUser(): Builder
    {
        return $this->loanApplicationQueryForUser();
    }

    /**
     * @return array{from: string, to: string, label: string}
     */
    public function currentFiscalYearContext(): array
    {
        [$from, $to] = $this->currentFiscalYearRange();

        return [
            'from' => $from,
            'to' => $to,
            'label' => $this->currentFiscalYearKey(),
        ];
    }

    public function currentFiscalYearKey(): string
    {
        return FiscalYear::currentKey();
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function currentFiscalYearRange(): array
    {
        return FiscalYear::dateRange($this->currentFiscalYearKey());
    }

    protected function applyCurrentFiscalYear(Builder $query, string $column = 'created_at'): void
    {
        [$from, $to] = $this->currentFiscalYearRange();

        $query->where($column, '>=', Carbon::parse($from)->startOfDay())
            ->where($column, '<=', Carbon::parse($to)->endOfDay());
    }

    protected function loanApplicationQueryForUser(): Builder
    {
        $query = Loan::query();
        $user = Auth::user();

        if ($user?->hasRole('applicant')) {
            $query->where('user_id', $user->id);
        }

        $this->applyCurrentFiscalYear($query, 'created_at');

        return $query;
    }

    protected function cacheKeyPrefix(string $prefix): string
    {
        return "{$prefix}.v7.".Auth::id().'.'.$this->currentFiscalYearKey().'.'.app()->getLocale();
    }

    public function forUser(): array
    {
        $user = Auth::user();

        return Cache::remember($this->cacheKeyPrefix('stats.user'), $this->cacheTtl, function () use ($user) {
            $query = $this->loanApplicationQueryForUser();
            $people = $this->peopleCounts($query);

            $row = (clone $query)->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status IN ('pending','received','in_review','awaiting_applicant') THEN 1 ELSE 0 END) as pending")
                ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved")
                ->selectRaw("SUM(CASE WHEN status IN ('approved','ready_for_disbursement','disbursed') THEN 1 ELSE 0 END) as approved_total")
                ->selectRaw("SUM(CASE WHEN status = 'ready_for_disbursement' THEN 1 ELSE 0 END) as ready_for_disbursement")
                ->selectRaw("SUM(CASE WHEN status = 'disbursed' THEN 1 ELSE 0 END) as disbursed")
                ->first();

            $disbursedRows = (clone $query)
                ->where('status', 'disbursed')
                ->where('disbursed_amount', '>', 0)
                ->get(['disbursed_amount', 'loan_type']);

            $individualAmount = (float) $disbursedRows
                ->where('loan_type', 'individual')
                ->sum(fn (Loan $loan) => (float) ($loan->disbursed_amount ?? 0));
            $groupAmount = (float) $disbursedRows
                ->where('loan_type', 'group')
                ->sum(fn (Loan $loan) => (float) ($loan->disbursed_amount ?? 0));
            $totalAmount = $individualAmount + $groupAmount;

            return [
                'total' => $people['total'],
                'loan_count' => $people['loan_count'],
                'individual_count' => $people['individual_count'],
                'group_count' => $people['group_count'],
                'group_members_count' => $people['group_members_count'],
                'my_loans' => $user->hasRole('applicant') ? $people['total'] : 0,
                'pending' => (int) ($row->pending ?? 0),
                // Awaiting assignment / currently approved only (chief).
                'approved' => (int) ($row->approved ?? 0),
                // Cumulative approved path, including ready + disbursed (general dashboards).
                'approved_total' => (int) ($row->approved_total ?? 0),
                'ready_for_disbursement' => (int) ($row->ready_for_disbursement ?? 0),
                'disbursed' => (int) ($row->disbursed ?? 0),
                'total_amount' => $totalAmount,
                'individual_amount' => $individualAmount,
                'group_amount' => $groupAmount,
            ];
        });
    }

    public function paginatedRecentLoans(
        ?string $filter = null,
        ?string $search = null,
        ?string $sort = null,
        int $perPage = 15,
        ?string $type = null,
    ): LengthAwarePaginator {
        $filter = $this->normalizeRecentFilter($filter);
        $sort = $this->normalizeListSort($sort);
        $type = $this->normalizeRecentType($type);

        $query = $this->loanApplicationQueryForUser()
            ->select([
                'id', 'loan_track_id', 'loan_type', 'loan_group_id', 'applicant_id',
                'requested_amount', 'disbursed_amount', 'current_step', 'status', 'user_id', 'officer_id', 'created_at',
            ])
            ->with([
                'applicant:id,full_name,first_name,last_name',
                'group:id,name',
                'businessDetails:loan_id,ward_id,council_id,business_name',
                'businessDetails.ward:id,name',
                'approvalLevels:id,loan_id,user_id',
            ]);

        $this->applyRecentFilter($query, $filter);
        $this->applyRecentType($query, $type);
        $this->applyListSearch($query, $search);
        $this->applyActionableFirst($query);
        $this->applyListSort($query, $sort);

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->fragment('recent-applications');
    }

    public function recentSortOptions(): array
    {
        return $this->listSortOptions();
    }

    public function normalizeRecentFilter(?string $filter): string
    {
        return in_array($filter, ['all', 'pending', 'approved', 'ready_for_disbursement', 'disbursed'], true)
            ? $filter
            : 'all';
    }

    public function normalizeRecentType(?string $type): string
    {
        return in_array($type, ['individual', 'group'], true) ? $type : 'all';
    }

    protected function applyRecentType(Builder $query, string $type): void
    {
        if ($type === 'individual' || $type === 'group') {
            $query->where('loan_type', $type);
        }
    }

    /**
     * People in scope: each individual application plus each group member once per group.
     *
     * @return array{total: int, loan_count: int, individual_count: int, group_count: int, group_members_count: int}
     */
    protected function peopleCounts(Builder $query): array
    {
        $loanCount = (int) (clone $query)->count();
        $individualCount = (int) (clone $query)->where('loan_type', 'individual')->count();

        $groupLoanQuery = (clone $query)->where('loan_type', 'group');
        $orphanGroupLoans = (int) (clone $groupLoanQuery)->whereNull('loan_group_id')->count();
        $groupIds = (clone $groupLoanQuery)
            ->whereNotNull('loan_group_id')
            ->distinct()
            ->pluck('loan_group_id')
            ->filter()
            ->values();

        $groupCount = $groupIds->count() + $orphanGroupLoans;
        $groupMembersCount = $orphanGroupLoans;

        if ($groupIds->isNotEmpty()) {
            $membersByGroup = DB::table('loan_group_members')
                ->select('loan_group_id', DB::raw('COUNT(*) as total'))
                ->whereIn('loan_group_id', $groupIds)
                ->groupBy('loan_group_id')
                ->pluck('total', 'loan_group_id');

            $applicantsByGroup = DB::table('applicant_loan_group')
                ->select('loan_group_id', DB::raw('COUNT(*) as total'))
                ->whereIn('loan_group_id', $groupIds)
                ->groupBy('loan_group_id')
                ->pluck('total', 'loan_group_id');

            foreach ($groupIds as $groupId) {
                $members = (int) ($membersByGroup[$groupId] ?? 0);
                if ($members < 1) {
                    $members = (int) ($applicantsByGroup[$groupId] ?? 0);
                }
                $groupMembersCount += $members > 0 ? $members : 1;
            }
        }

        return [
            'individual_count' => $individualCount,
            'group_count' => $groupCount,
            'group_members_count' => $groupMembersCount,
            'total' => $individualCount + $groupMembersCount,
            'loan_count' => $loanCount,
        ];
    }

    public function normalizeRecentSort(?string $sort): string
    {
        return $this->normalizeListSort($sort);
    }

    /**
     * @return array{
     *     count: int,
     *     active_count: int,
     *     cleared_count: int,
     *     total_disbursed: float,
     *     total_paid: float,
     *     total_outstanding: float,
     *     collection_rate: int,
     *     all: array,
     *     individual: array,
     *     group: array
     * }
     */
    public function disbursedCollectionSummary(?string $type = null): array
    {
        $empty = $this->emptyDisbursedSummary();
        $loans = $this->loanApplicationQueryForUser()
            ->where('status', 'disbursed')
            ->get(['id', 'loan_type', 'disbursed_amount']);

        if ($loans->isEmpty()) {
            return array_merge($empty, [
                'all' => $empty,
                'individual' => $empty,
                'group' => $empty,
            ]);
        }

        $payments = LoanPayment::query()
            ->whereIn('loan_id', $loans->pluck('id'))
            ->get(['loan_id', 'amount_disbursed', 'amount_paid', 'outstanding_debt']);

        $all = $this->summarizeDisbursedLoans($loans, $payments);
        $individual = $this->summarizeDisbursedLoans($loans->where('loan_type', 'individual'), $payments);
        $group = $this->summarizeDisbursedLoans($loans->where('loan_type', 'group'), $payments);
        $selected = match ($this->normalizeRecentType($type)) {
            'individual' => $individual,
            'group' => $group,
            default => $all,
        };

        return array_merge($selected, [
            'all' => $all,
            'individual' => $individual,
            'group' => $group,
        ]);
    }

    /**
     * @return array{count: int, active_count: int, cleared_count: int, total_disbursed: float, total_paid: float, total_outstanding: float, collection_rate: int}
     */
    protected function emptyDisbursedSummary(): array
    {
        return [
            'count' => 0,
            'active_count' => 0,
            'cleared_count' => 0,
            'total_disbursed' => 0.0,
            'total_paid' => 0.0,
            'total_outstanding' => 0.0,
            'collection_rate' => 0,
        ];
    }

    /**
     * @param  iterable<int, Loan>  $loans
     * @param  \Illuminate\Support\Collection<int, LoanPayment>  $payments
     * @return array{count: int, active_count: int, cleared_count: int, total_disbursed: float, total_paid: float, total_outstanding: float, collection_rate: int}
     */
    protected function summarizeDisbursedLoans(iterable $loans, $payments): array
    {
        $loans = collect($loans)->values();
        $count = $loans->count();

        if ($count === 0) {
            return $this->emptyDisbursedSummary();
        }

        $ids = $loans->pluck('id')->all();
        $typePayments = $payments->whereIn('loan_id', $ids);
        $totalDisbursed = (float) $typePayments->sum(fn (LoanPayment $payment) => (float) ($payment->amount_disbursed ?? 0));
        if ($totalDisbursed <= 0) {
            $totalDisbursed = (float) $loans->sum(fn (Loan $loan) => (float) ($loan->disbursed_amount ?? 0));
        }

        $totalPaid = (float) $typePayments->sum(fn (LoanPayment $payment) => (float) ($payment->amount_paid ?? 0));
        $totalOutstanding = (float) $typePayments->sum(fn (LoanPayment $payment) => (float) ($payment->outstanding_debt ?? 0));
        $activeCount = $typePayments->filter(fn (LoanPayment $payment) => (float) $payment->outstanding_debt > 0)->count();
        $covered = $typePayments->pluck('loan_id')->unique()->count();
        $activeCount += max(0, $count - $covered);
        $clearedCount = max(0, $count - $activeCount);
        $collectable = $totalPaid + $totalOutstanding;

        return [
            'count' => $count,
            'active_count' => $activeCount,
            'cleared_count' => $clearedCount,
            'total_disbursed' => $totalDisbursed,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'collection_rate' => $collectable > 0
                ? (int) min(100, round(($totalPaid / $collectable) * 100))
                : 0,
        ];
    }

    protected function applyRecentFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'pending' => $query->whereIn('status', ['pending', 'received', 'in_review', 'awaiting_applicant']),
            // Chief: awaiting assignment only. Others: whole approved path incl. disbursed.
            'approved' => Auth::user()?->hasRole('chief')
                ? $query->where('status', 'approved')
                : $query->whereIn('status', ['approved', 'ready_for_disbursement', 'disbursed']),
            'ready_for_disbursement' => $query->where('status', 'ready_for_disbursement'),
            'disbursed' => $query->where('status', 'disbursed'),
            default => null,
        };
    }

    public function monthlyApplications(int $months = 6): array
    {
        return Cache::remember($this->cacheKeyPrefix('stats.monthly.apps'), $this->cacheTtl, function () use ($months) {
            return $this->monthlySeries('created_at', 'count', $months);
        });
    }

    public function monthlyDisbursements(int $months = 6): array
    {
        return Cache::remember($this->cacheKeyPrefix('stats.monthly.disb'), $this->cacheTtl, function () use ($months) {
            return $this->monthlySeries('updated_at', 'sum_disbursed', $months);
        });
    }

    protected function monthlySeries(string $dateColumn, string $mode, int $months): array
    {
        [$fyFrom, $fyTo] = $this->currentFiscalYearRange();
        $start = Carbon::parse($fyFrom)->startOfMonth();
        $fyEnd = Carbon::parse($fyTo)->endOfMonth();
        $end = Carbon::now()->endOfMonth()->lt($fyEnd)
            ? Carbon::now()->endOfMonth()
            : $fyEnd;

        $query = $this->loanApplicationQueryForUser()
            ->whereBetween($dateColumn, [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        if ($mode === 'sum_disbursed') {
            $query->where('status', 'disbursed');
        }

        $aggregate = $mode === 'sum_disbursed'
            ? 'SUM(disbursed_amount)'
            : 'COUNT(*)';

        $periodExpr = $this->monthPeriodExpression($dateColumn);

        $counts = $query
            ->selectRaw("{$periodExpr} as period")
            ->selectRaw("{$aggregate} as total")
            ->groupBy('period')
            ->pluck('total', 'period');

        $labels = [];
        $data = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('M Y');
            $data[] = $mode === 'sum_disbursed'
                ? (float) ($counts[$key] ?? 0)
                : (int) ($counts[$key] ?? 0);
            $cursor->addMonth();
        }

        return compact('labels', 'data');
    }

    public function stepBreakdown(): array
    {
        return Cache::remember($this->cacheKeyPrefix('stats.pipeline'), $this->cacheTtl, function () {
            ['labels' => $labels, 'shortLabels' => $shortLabels] = WorkflowSteps::pipelineLabels();

            $counts = $this->loanQueryForUser()
                ->select('current_step', DB::raw('count(*) as total'))
                ->groupBy('current_step')
                ->pluck('total', 'current_step');

            $data = [];

            foreach (array_keys(WorkflowSteps::LABELS) as $num) {
                $data[] = (int) ($counts[$num] ?? 0);
            }

            return compact('labels', 'shortLabels', 'data');
        });
    }

    public function statusBreakdown(): array
    {
        return Cache::remember($this->cacheKeyPrefix('stats.status'), $this->cacheTtl, function () {
            $statuses = $this->loanQueryForUser()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'labels' => $statuses->keys()->map(fn ($s) => ucwords(str_replace('_', ' ', $s)))->values()->toArray(),
                'data' => $statuses->values()->map(fn ($v) => (int) $v)->toArray(),
            ];
        });
    }

    public function byRegion(): array
    {
        return Cache::remember($this->cacheKeyPrefix('stats.region'), $this->cacheTtl, function () {
            $rows = DB::table('business_details')
                ->join('regions', 'business_details.region_id', '=', 'regions.id')
                ->whereIn('business_details.loan_id', $this->loanQueryForUser()->select('id'))
                ->select('regions.name', DB::raw('count(*) as total'))
                ->groupBy('regions.name')
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            if ($rows->isEmpty()) {
                return ['labels' => [], 'data' => []];
            }

            return [
                'labels' => $rows->pluck('name')->toArray(),
                'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
            ];
        });
    }

    public static function flushForUser(?int $userId = null): void
    {
        $userId ??= Auth::id();
        if (! $userId) {
            return;
        }

        $fyKey = FiscalYear::currentKey();
        $locale = app()->getLocale();
        $prefixes = ['stats.user', 'stats.monthly.apps', 'stats.monthly.disb', 'stats.pipeline', 'stats.status', 'stats.region'];

        foreach ($prefixes as $prefix) {
            Cache::forget("{$prefix}.v7.{$userId}.{$fyKey}.{$locale}");
            Cache::forget("{$prefix}.v6.{$userId}.{$fyKey}.{$locale}");
            Cache::forget("{$prefix}.v5.{$userId}.{$fyKey}.{$locale}");
            Cache::forget("{$prefix}.v5.{$userId}.{$fyKey}");
            Cache::forget("{$prefix}.v4.{$userId}.{$fyKey}");
            Cache::forget("{$prefix}.v3.{$userId}.{$fyKey}");
            Cache::forget("{$prefix}.v2.{$userId}.{$fyKey}");
            Cache::forget("{$prefix}.{$userId}.{$fyKey}");
            Cache::forget("{$prefix}.{$userId}");
        }
    }

    public function applicantsCount(): int
    {
        return Applicant::count();
    }

    protected function monthPeriodExpression(string $dateColumn): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$dateColumn})",
            'pgsql' => "to_char({$dateColumn}, 'YYYY-MM')",
            default => "DATE_FORMAT({$dateColumn}, '%Y-%m')",
        };
    }
}
