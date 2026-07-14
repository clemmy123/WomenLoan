<?php

namespace App\Support;

use App\Models\User;
use Closure;

/**
 * First authenticated landing URL the user is allowed to open.
 * Avoids hardcoding /dashboard when a custom role lacks "view dashboard".
 */
class AccessibleHome
{
    /**
     * @return list<array{0: Closure(User): bool, 1: string}>
     */
    private static function candidates(): array
    {
        return [
            [fn (User $u) => $u->can('view dashboard'), 'dashboard'],
            [fn (User $u) => $u->can('view administration dashboard'), 'admin.dashboard'],
            [fn (User $u) => self::canViewStaffLoans($u), 'loan-applications.index'],
            [fn (User $u) => $u->can('view repayments'), 'repayments.index'],
            [fn (User $u) => $u->can('view loan by track id'), 'loans.track'],
            [fn (User $u) => $u->can('view reports overview'), 'reports.index'],
            [fn (User $u) => $u->can('view application reports'), 'reports.applications.index'],
            [fn (User $u) => $u->can('view payment reports'), 'reports.analytical.overview'],
            [fn (User $u) => $u->can('view outstanding reports'), 'reports.analytical.outstanding'],
            [fn (User $u) => $u->can('view overdue reports'), 'reports.analytical.overdue'],
            [fn (User $u) => $u->can('view by region reports'), 'reports.by-region.index'],
            [fn (User $u) => $u->can('view by type reports'), 'reports.by-type.index'],
            [fn (User $u) => $u->can('view by sector reports'), 'reports.by-sector.index'],
            [fn (User $u) => $u->can('view by bank reports'), 'reports.by-bank.index'],
            [fn (User $u) => $u->can('view by monthly reports'), 'reports.by-monthly.index'],
            [fn (User $u) => $u->can('view by age reports'), 'reports.by-age.index'],
            [fn (User $u) => $u->can('manage users'), 'admin.users.index'],
            [fn (User $u) => $u->can('manage roles'), 'admin.roles.index'],
            [fn (User $u) => $u->can('view audit logs'), 'admin.audit.index'],
            [fn (User $u) => $u->can('manage loan groups'), 'loan-groups.index'],
            [fn (User $u) => $u->can('register applicant') || $u->can('manage applicants'), 'applicants.index'],
        ];
    }

    public static function url(User $user): string
    {
        foreach (self::candidates() as [$allowed, $routeName]) {
            if ($allowed($user)) {
                return route($routeName);
            }
        }

        // Authenticated fallback that does not require a catalog permission.
        return route('profile.password.edit');
    }

    private static function canViewStaffLoans(User $user): bool
    {
        return $user->can('view ward loans')
            || $user->can('view council loans')
            || $user->can('view region loans')
            || $user->can('view all loans')
            || $user->can('view own loans')
            || $user->can('assign accountant')
            || $user->can('disburse loan');
    }
}
