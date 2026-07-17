<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Loan;
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
        return "{$prefix}.v2.".Auth::id().'.'.$this->currentFiscalYearKey();
    }

    public function forUser(): array
    {
        $user = Auth::user();

        return Cache::remember($this->cacheKeyPrefix('stats.user'), $this->cacheTtl, function () use ($user) {
            $query = $this->loanApplicationQueryForUser();

            $row = (clone $query)->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status IN ('pending','received','in_review','awaiting_applicant') THEN 1 ELSE 0 END) as pending")
                ->selectRaw("SUM(CASE WHEN status IN ('approved','ready_for_disbursement','disbursed') THEN 1 ELSE 0 END) as approved")
                ->selectRaw("SUM(CASE WHEN status = 'disbursed' THEN 1 ELSE 0 END) as disbursed")
                ->first();

            $totalAmount = (clone $query)
                ->where('status', 'disbursed')
                ->where('disbursed_amount', '>', 0)
                ->get(['disbursed_amount'])
                ->sum(fn (Loan $loan) => (float) ($loan->disbursed_amount ?? 0));

            return [
                'total' => (int) ($row->total ?? 0),
                'my_loans' => $user->hasRole('applicant') ? (int) ($row->total ?? 0) : 0,
                'pending' => (int) ($row->pending ?? 0),
                'approved' => (int) ($row->approved ?? 0),
                'disbursed' => (int) ($row->disbursed ?? 0),
                'total_amount' => (float) $totalAmount,
            ];
        });
    }

    public function paginatedRecentLoans(
        ?string $filter = null,
        ?string $search = null,
        ?string $sort = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $filter = $this->normalizeRecentFilter($filter);
        $sort = $this->normalizeListSort($sort);

        $query = $this->loanApplicationQueryForUser()
            ->select([
                'id', 'loan_track_id', 'loan_type', 'loan_group_id', 'applicant_id',
                'requested_amount', 'current_step', 'status', 'user_id', 'officer_id', 'created_at',
            ])
            ->with([
                'applicant:id,full_name,first_name,last_name',
                'group:id,name',
                'businessDetails:loan_id,ward_id,council_id,business_name',
                'businessDetails.ward:id,name',
                'approvalLevels:id,loan_id,user_id',
            ]);

        $this->applyRecentFilter($query, $filter);
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
        return in_array($filter, ['all', 'pending', 'approved', 'disbursed'], true) ? $filter : 'all';
    }

    public function normalizeRecentSort(?string $sort): string
    {
        return $this->normalizeListSort($sort);
    }

    protected function applyRecentFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'pending' => $query->whereIn('status', ['pending', 'received', 'in_review', 'awaiting_applicant']),
            'approved' => $query->whereIn('status', ['approved', 'ready_for_disbursement', 'disbursed']),
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
        $prefixes = ['stats.user', 'stats.monthly.apps', 'stats.monthly.disb', 'stats.pipeline', 'stats.status', 'stats.region'];

        foreach ($prefixes as $prefix) {
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
