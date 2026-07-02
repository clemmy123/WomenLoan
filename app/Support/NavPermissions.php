<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

class NavPermissions
{
    public static function for(Authenticatable $user): array
    {
        return [
            'viewDashboard' => $user->can('view dashboard'),
            'trackLoan' => $user->can('view loan by track id'),
            'isApplicant' => $user->hasRole('applicant'),
            'createLoan' => $user->can('create loan application') && $user->hasCompletedProfile(),
            'registerApplicant' => $user->can('register applicant'),
            'manageApplicants' => $user->can('manage applicants') && ! $user->hasRole('applicant'),
            'viewStaffLoans' => $user->can('view ward loans')
                || $user->can('view council loans')
                || $user->can('view region loans')
                || $user->can('view all loans'),
            'manageGroups' => $user->can('manage loan groups'),
            'viewRepayments' => $user->can('view repayments'),
            'viewReports' => $user->can('view reports'),
            'manageUsers' => $user->can('manage users'),
            'manageRoles' => $user->can('manage roles'),
        ];
    }
}
