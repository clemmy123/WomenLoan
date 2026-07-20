<?php

use App\Models\Loan;
use App\Models\User;

if (! function_exists('loan_needs_user_action')) {
    /**
     * True when the current user has a forward/approve workflow action on this loan
     * (not rollback-only). Used for list priority and "needs action" alerts.
     */
    function loan_needs_user_action(Loan $loan, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $auth = app(\App\Services\WorkflowAuthorizationService::class);

        foreach ([
            'forward_council',
            'forward_ministry',
            'propose_amount',
            'accept_amount',
            'forward_ass_dir',
            'forward_director',
            'forward_km',
            'approve_km',
            'assign_accountant',
            'disburse',
        ] as $action) {
            if ($auth->canPerform($user, $loan, $action)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('loan_has_workflow_actions')) {
    function loan_has_workflow_actions(Loan $loan, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return loan_needs_user_action($loan, $user)
            || app(\App\Services\WorkflowAuthorizationService::class)->canPerform($user, $loan, 'rollback_step');
    }
}

if (! function_exists('workflow_attachment_label')) {
    function workflow_attachment_label(?string $action): string
    {
        return match ($action) {
            'forwarded_to_council' => __('workflow.supervision_document'),
            'forwarded_to_ass_dir' => __('workflow.committee_minutes'),
            default => __('common.attachment'),
        };
    }
}
