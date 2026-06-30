<?php

namespace App\Services;

use App\Models\ApprovalLevel;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\WorkflowSteps;

class LoanWorkflowService
{
    public const STEPS = WorkflowSteps::ROLES;

    public function process(Loan $loan, string $action, array $data = []): Loan
    {
        return DB::transaction(function () use ($loan, $action, $data) {
            $user = Auth::user();

            match ($action) {
                'receive' => $this->wardReceive($loan, $user, $data),
                'forward_ministry' => $this->forward($loan, $user, 2, 'forwarded_to_ministry', $data),
                'propose_amount' => $this->proposeAmount($loan, $user, $data),
                'send_to_applicant' => $this->forward($loan, $user, 3, 'sent_to_applicant', $data),
                'accept_amount' => $this->applicantAccept($loan, $user, true, $data),
                'decline_amount' => $this->applicantAccept($loan, $user, false, $data),
                'forward_ass_dir' => $this->forward($loan, $user, 5, 'forwarded_to_ass_dir', $data),
                'forward_director' => $this->forward($loan, $user, 6, 'forwarded_to_director', $data),
                'forward_km' => $this->forward($loan, $user, 7, 'forwarded_to_km', $data),
                'approve_km' => $this->approveKm($loan, $user, $data),
                'assign_accountant' => $this->assignAccountant($loan, $user, $data),
                'disburse' => $this->disburse($loan, $user, $data),
                default => throw new \InvalidArgumentException("Unknown action: {$action}"),
            };

            DashboardStatsService::flushForUser($user->id);

            return $loan->fresh(['applicant', 'businessDetails', 'approvalLevels.user']);
        });
    }

    protected function logAction(Loan $loan, User $user, int $step, string $action, array $data = []): void
    {
        ApprovalLevel::create([
            'loan_id' => $loan->id,
            'user_id' => $user->id,
            'step_number' => $step,
            'action_taken' => $action,
            'proposed_amount' => $data['proposed_amount'] ?? $loan->proposed_amount ?? 0,
            'attachment_path' => $data['attachment_path'] ?? null,
            'comments' => $data['comments'] ?? null,
        ]);

        $history = $loan->approval_history ?? [];
        $history[] = [
            'step' => $step,
            'action' => $action,
            'user' => $user->name,
            'at' => now()->toIso8601String(),
            'comments' => $data['comments'] ?? null,
        ];
        $loan->update(['approval_history' => $history]);
    }

    protected function wardReceive(Loan $loan, User $user, array $data): void
    {
        $this->logAction($loan, $user, 1, 'received', $data);
        $loan->update(['status' => 'received']);
    }

    protected function forward(Loan $loan, User $user, int $nextStep, string $action, array $data): void
    {
        $this->logAction($loan, $user, $loan->current_step, $action, $data);
        $loan->update([
            'current_step' => $nextStep,
            'status' => 'in_review',
            'comments' => $data['comments'] ?? $loan->comments,
        ]);
    }

    protected function proposeAmount(Loan $loan, User $user, array $data): void
    {
        $this->logAction($loan, $user, 2, 'proposed_amount', $data);
        $loan->update([
            'proposed_amount' => $data['proposed_amount'],
            'current_step' => 3,
            'status' => 'awaiting_applicant',
            'applicant_acceptance' => 'pending',
        ]);
    }

    protected function applicantAccept(Loan $loan, User $user, bool $accepted, array $data): void
    {
        $this->logAction($loan, $user, 3, $accepted ? 'accepted' : 'declined', $data);
        $loan->update([
            'applicant_acceptance' => $accepted ? 'accepted' : 'declined',
            'current_step' => $accepted ? 4 : 2,
            'status' => $accepted ? 'in_review' : 'declined_by_applicant',
        ]);
    }

    protected function approveKm(Loan $loan, User $user, array $data): void
    {
        $this->logAction($loan, $user, 7, 'approved', $data);
        $loan->update([
            'current_step' => 8,
            'status' => 'approved',
            'approved_by' => $user->name,
        ]);
    }

    protected function assignAccountant(Loan $loan, User $user, array $data): void
    {
        $this->logAction($loan, $user, 8, 'assigned_accountant', $data);
        $loan->update([
            'officer_id' => $data['accountant_id'],
            'current_step' => 9,
            'status' => 'ready_for_disbursement',
        ]);
    }

    protected function disburse(Loan $loan, User $user, array $data): void
    {
        $this->logAction($loan, $user, 9, 'disbursed', $data);
        $loan->update([
            'disbursed_amount' => $data['disbursed_amount'] ?? $loan->proposed_amount,
            'date_issued' => now()->toDateString(),
            'status' => 'disbursed',
            'current_step' => 9,
        ]);
    }
}
