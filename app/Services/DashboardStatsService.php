<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Loan;
use App\Support\WorkflowSteps;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    protected int $cacheTtl = 45;

    protected function loanQueryForUser(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Loan::query();
        $user = Auth::user();

        if ($user?->hasRole('applicant')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function forUser(): array
    {
        $user = Auth::user();

        return Cache::remember("stats.user.{$user->id}", $this->cacheTtl, function () use ($user) {
            $query = $this->loanQueryForUser();

            $row = (clone $query)->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status IN ('pending','received','in_review','awaiting_applicant') THEN 1 ELSE 0 END) as pending")
                ->selectRaw("SUM(CASE WHEN status IN ('approved','ready_for_disbursement') THEN 1 ELSE 0 END) as approved")
                ->selectRaw("SUM(CASE WHEN status = 'disbursed' THEN 1 ELSE 0 END) as disbursed")
                ->selectRaw("SUM(CASE WHEN status = 'disbursed' THEN disbursed_amount ELSE 0 END) as total_amount")
                ->selectRaw('SUM(CASE WHEN YEAR(created_at) = ? AND MONTH(created_at) = ? THEN 1 ELSE 0 END) as this_month', [
                    now()->year,
                    now()->month,
                ])
                ->first();

            return [
                'total' => (int) ($row->total ?? 0),
                'my_loans' => $user->hasRole('applicant') ? (int) ($row->total ?? 0) : 0,
                'pending' => (int) ($row->pending ?? 0),
                'approved' => (int) ($row->approved ?? 0),
                'disbursed' => (int) ($row->disbursed ?? 0),
                'total_amount' => (float) ($row->total_amount ?? 0),
                'this_month' => (int) ($row->this_month ?? 0),
            ];
        });
    }

    public function recentLoans(int $limit = 6): Collection
    {
        $userId = Auth::id();

        return Cache::remember("stats.recent.{$userId}", $this->cacheTtl, function () use ($limit) {
            return $this->loanQueryForUser()
                ->select(['id', 'loan_track_id', 'applicant_id', 'requested_amount', 'current_step', 'status', 'created_at'])
                ->with(['applicant:id,full_name,first_name,last_name'])
                ->latest()
                ->limit($limit)
                ->get();
        });
    }

    public function monthlyApplications(int $months = 6): array
    {
        $userId = Auth::id();

        return Cache::remember("stats.monthly.apps.{$userId}", $this->cacheTtl, function () use ($months) {
            return $this->monthlySeries('created_at', 'count', $months);
        });
    }

    public function monthlyDisbursements(int $months = 6): array
    {
        $userId = Auth::id();

        return Cache::remember("stats.monthly.disb.{$userId}", $this->cacheTtl, function () use ($months) {
            return $this->monthlySeries('updated_at', 'sum_disbursed', $months);
        });
    }

    protected function monthlySeries(string $dateColumn, string $mode, int $months): array
    {
        $start = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $query = $this->loanQueryForUser()->where($dateColumn, '>=', $start);

        if ($mode === 'sum_disbursed') {
            $query->where('status', 'disbursed');
        }

        $aggregate = $mode === 'sum_disbursed'
            ? 'SUM(disbursed_amount)'
            : 'COUNT(*)';

        $counts = $query
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as period")
            ->selectRaw("{$aggregate} as total")
            ->groupBy('period')
            ->pluck('total', 'period');

        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $labels[] = $date->format('M Y');
            $data[] = $mode === 'sum_disbursed'
                ? (float) ($counts[$key] ?? 0)
                : (int) ($counts[$key] ?? 0);
        }

        return compact('labels', 'data');
    }

    public function stepBreakdown(): array
    {
        $userId = Auth::id();

        return Cache::remember("stats.pipeline.{$userId}", $this->cacheTtl, function () {
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
        $userId = Auth::id();

        return Cache::remember("stats.status.{$userId}", $this->cacheTtl, function () {
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
        $userId = Auth::id();

        return Cache::remember("stats.region.{$userId}", $this->cacheTtl, function () {
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

        foreach (['stats.user', 'stats.monthly.apps', 'stats.monthly.disb', 'stats.pipeline', 'stats.status', 'stats.region', 'stats.recent'] as $prefix) {
            Cache::forget("{$prefix}.{$userId}");
        }
    }

    public function applicantsCount(): int
    {
        return Applicant::count();
    }
}
