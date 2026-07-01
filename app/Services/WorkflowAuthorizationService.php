<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;

class WorkflowAuthorizationService
{
    public function canPerform(User $user, Loan $loan, string $action): bool
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        $step = $loan->current_step;
        $status = $loan->status;

        return match ($action) {
            'receive' => $user->can('receive application') && $step === 1 && $status === 'pending',
            'forward_ministry' => $user->can('forward to ministry') && $step === 1 && $status === 'received',
            'propose_amount', 'send_to_applicant' => $user->can('propose loan amount') && in_array($step, [2, 4], true),
            'accept_amount', 'decline_amount' => $user->hasRole('applicant') && $step === 3,
            'forward_ass_dir' => $user->can('forward to assistant director') && $step === 4,
            'forward_director' => $user->can('forward to director') && $step === 5,
            'forward_km' => $user->can('forward to km') && $step === 6,
            'approve_km' => $user->can('approve as km') && $step === 7,
            'assign_accountant' => $user->can('assign accountant') && $step === 8,
            'disburse' => $user->can('disburse loan') && $step === 9 && $loan->officer_id === $user->id,
            'rollback_step' => $this->canRollback($user, $loan),
            default => false,
        };
    }

    public function authorizeOrAbort(User $user, Loan $loan, string $action): void
    {
        if (! $this->canPerform($user, $loan, $action)) {
            abort(403);
        }
    }

    protected function canRollback(User $user, Loan $loan): bool
    {
        if ($loan->status === 'disbursed' || $loan->current_step <= 1) {
            return false;
        }

        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        if (! $user->can('rollback workflow step')) {
            return false;
        }

        return match ($loan->current_step) {
            2, 3, 4 => $user->can('propose loan amount') || $user->can('forward to assistant director'),
            5 => $user->can('comment as assistant director'),
            6 => $user->can('comment as director'),
            7 => $user->can('forward to km'),
            8 => $user->can('approve as km'),
            9 => $user->can('assign accountant') || $user->can('disburse loan'),
            default => false,
        };
    }
}
