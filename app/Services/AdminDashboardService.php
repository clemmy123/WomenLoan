<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AdminDashboardService
{
    public const APPLICANT_ROLE = 'applicant';

    public function summary(): array
    {
        $staff = $this->staffUsersQuery();
        $activeUsers = (clone $staff)->where('is_active', true)->count();
        $inactiveUsers = (clone $staff)->where('is_active', false)->count();
        $rolesCount = $this->staffRolesQuery()->count();

        $auditToday = Activity::query()
            ->whereDate('created_at', today())
            ->count();

        $auditWeek = Activity::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            // Main totals = active staff only. Deactivated users are tracked separately.
            'total_users' => $activeUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'roles_count' => $rolesCount,
            'audit_today' => $auditToday,
            'audit_week' => $auditWeek,
        ];
    }

    /**
     * @return Collection<int, array{role: string, label: string, count: int}>
     */
    public function usersByRole(): Collection
    {
        return $this->staffRolesQuery()
            ->withCount([
                'users as users_count' => fn (Builder $query) => $query->where('is_active', true),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'role' => $role->name,
                'label' => role_label($role->name),
                'count' => (int) $role->users_count,
            ]);
    }

    /**
     * Daily audit activity counts for the last N days (including today).
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    public function auditActivitySeries(int $days = 7): array
    {
        $days = max(1, $days);
        $start = now()->subDays($days - 1)->startOfDay();
        $periodExpr = $this->dayPeriodExpression('created_at');

        $counts = Activity::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("{$periodExpr} as period")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $labels = [];
        $data = [];
        $cursor = $start->copy();

        for ($i = 0; $i < $days; $i++) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d M');
            $data[] = (int) ($counts[$key] ?? 0);
            $cursor->addDay();
        }

        return compact('labels', 'data');
    }

    public function recentAudit(int $limit = 8): Collection
    {
        return Activity::query()
            ->with('causer')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    protected function staffUsersQuery(): Builder
    {
        return User::query()->whereHas(
            'roles',
            fn (Builder $roles) => $roles->where('name', '!=', self::APPLICANT_ROLE)
        );
    }

    protected function staffRolesQuery(): Builder
    {
        return Role::query()->where('name', '!=', self::APPLICANT_ROLE);
    }

    protected function dayPeriodExpression(string $dateColumn): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d', {$dateColumn})",
            'pgsql' => "to_char({$dateColumn}, 'YYYY-MM-DD')",
            default => "DATE({$dateColumn})",
        };
    }
}
