<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class AdminDashboardService
{
    public function summary(): array
    {
        $users = User::query();

        $totalUsers = (clone $users)->count();
        $activeUsers = (clone $users)->where('is_active', true)->count();
        $inactiveUsers = max(0, $totalUsers - $activeUsers);
        $rolesCount = Role::query()->count();

        $auditToday = Activity::query()
            ->whereDate('created_at', today())
            ->count();

        $auditWeek = Activity::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_users' => $totalUsers,
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
        return Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'role' => $role->name,
                'label' => role_label($role->name),
                'count' => (int) $role->users_count,
            ]);
    }

    public function recentAudit(int $limit = 8): Collection
    {
        return Activity::query()
            ->with('causer')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
