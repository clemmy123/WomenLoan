<?php

namespace App\Support;

use App\Models\User;

class NavPermissions
{
    public static function for(User $user): array
    {
        $reportFlags = [
            'viewReportsOverview' => $user->can('view reports overview'),
            'viewApplicationReports' => $user->can('view application reports'),
            'viewPaymentReports' => $user->can('view payment reports'),
            'viewOutstandingReports' => $user->can('view outstanding reports'),
            'viewOverdueReports' => $user->can('view overdue reports'),
            'viewByRegionReports' => $user->can('view by region reports'),
            'viewByTypeReports' => $user->can('view by type reports'),
            'viewBySectorReports' => $user->can('view by sector reports'),
            'viewByBankReports' => $user->can('view by bank reports'),
            'viewByMonthlyReports' => $user->can('view by monthly reports'),
            'viewByAgeReports' => $user->can('view by age reports'),
        ];

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
                || $user->can('view all loans')
                || $user->can('assign accountant')
                || $user->can('disburse loan'),
            'isChief' => $user->hasRole('chief'),
            'isAccountant' => $user->hasRole('accountant'),
            'manageGroups' => $user->can('manage loan groups'),
            'viewRepayments' => $user->can('view repayments'),
            ...$reportFlags,
            'viewReportsSection' => collect($reportFlags)->contains(true),
            'manageUsers' => $user->can('manage users'),
            'manageRoles' => $user->can('manage roles'),
            'viewAuditLogs' => $user->can('view audit logs'),
            'viewAdminDashboard' => $user->can('view administration dashboard'),
        ];
    }
}
