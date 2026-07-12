<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;

class WorkflowAuthorizationService
{
    public function canPerform(User $user, Loan $loan, string $action): bool
    {
        if ($action === 'rollback_step') {
            return $this->canRollback($user, $loan);
        }

        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        $step = $loan->current_step;
        $status = $loan->status;

        return match ($action) {
            'forward_ministry' => $user->can('forward to ministry')
                && $step === 1
                && $status === 'received'
                && app(CdoLoanScopeService::class)->canActOnLoan($user, $loan),
            'propose_amount', 'send_to_applicant' => $user->can('propose loan amount') && $step === 2,
            'accept_amount', 'decline_amount' => $user->hasRole('applicant') && $step === 3,
            'forward_ass_dir' => $user->can('forward to assistant director') && $step === 4,
            'forward_director' => $user->can('forward to director') && $step === 5,
            'forward_km' => $user->can('forward to km') && $step === 6,
            'approve_km' => $user->can('approve as km') && $step === 7,
            'assign_accountant' => $user->can('assign accountant') && $step === 8,
            'disburse' => $user->can('disburse loan')
                && $step === 9
                && $status === 'ready_for_disbursement'
                && $loan->officer_id === $user->id,
            default => false,
        };
    }

    public function authorizeOrAbort(User $user, Loan $loan, string $action): void
    {
        if (! $this->canPerform($user, $loan, $action)) {
            abort(403);
        }
    }

    /**
     * Rollback is available only to the current-step actor (forward or return for improvements).
     * Once the loan is approved (step 8+), rollback ends — chief/accountant never roll back.
     */
    protected function canRollback(User $user, Loan $loan): bool
    {
        if (in_array($loan->status, ['approved', 'ready_for_disbursement', 'disbursed'], true)) {
            return false;
        }

        if ($loan->current_step >= 8) {
            return false;
        }

        if ($user->hasRole(['chief', 'accountant'])) {
            return false;
        }

        if ($user->hasRole(['admin', 'super_admin'])) {
            return ! ($loan->current_step === 1 && $loan->status === 'pending');
        }

        if (! $user->can('rollback workflow step')) {
            return false;
        }

        if ($loan->current_step === 1 && $loan->status === 'received') {
            return $user->can('forward to ministry')
                && app(CdoLoanScopeService::class)->canActOnLoan($user, $loan);
        }

        // Current actor only — after they forward, the next step owner gets the buttons.
        return match ($loan->current_step) {
            2 => $user->can('propose loan amount'),
            4 => $user->can('forward to assistant director'),
            5 => $user->can('forward to director') || $user->can('comment as assistant director'),
            6 => $user->can('forward to km') || $user->can('comment as director'),
            7 => $user->can('approve as km'),
            default => false,
        };
    }
}
