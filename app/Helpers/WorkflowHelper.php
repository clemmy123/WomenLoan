<?php

use App\Models\Loan;
use App\Models\User;

if (! function_exists('loan_has_workflow_actions')) {
    function loan_has_workflow_actions(Loan $loan, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $step = $loan->current_step;

        return ($user->can('receive application') && $step === 1 && $loan->status === 'pending')
            || ($user->can('forward to ministry') && $step === 1 && $loan->status === 'received')
            || ($user->can('propose loan amount') && $step === 2)
            || ($user->hasRole('applicant') && $step === 3)
            || ($user->can('forward to assistant director') && $step === 4)
            || ($user->can('forward to director') && $step === 5)
            || ($user->can('forward to km') && $step === 6)
            || ($user->can('approve as km') && $step === 7)
            || ($user->can('assign accountant') && $step === 8)
            || ($user->can('disburse loan') && $step === 9 && $loan->status === 'ready_for_disbursement' && $loan->officer_id === $user->id)
            || app(\App\Services\WorkflowAuthorizationService::class)->canPerform($user, $loan, 'rollback_step');
    }
}

if (! function_exists('workflow_attachment_label')) {
    function workflow_attachment_label(?string $action): string
    {
        return match ($action) {
            'forwarded_to_ministry' => __('workflow.supervision_document'),
            'forwarded_to_ass_dir' => __('workflow.committee_minutes'),
            default => __('common.attachment'),
        };
    }
}
