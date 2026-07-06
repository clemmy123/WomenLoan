<?php

namespace App\Support;

use App\Models\User;

class NavPermissions
{
    public static function for(User $user): array
    {
        return [
            'viewDashboard' => $user->can('view dashboard'),
            'trackLoan' => $user->can('view loan by track id'),
            'isApplicant' => $user->hasRole('applicant'),
            'createLoan' => $user->can('create loan application')
                && $user->hasCompletedProfile()
                && ! $user->hasLoanApplication(),
            'preferredLoanType' => $user->applicant?->preferred_loan_type,
            'newApplicationLabel' => match ($user->applicant?->preferred_loan_type) {
                'group' => __('loans.continue_as_group'),
                'individual' => __('loans.continue_as_individual'),
                default => __('loans.start_new'),
            },
            'showGroupActions' => $user->applicant?->prefersGroupLoan() ?? false,
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
