<?php

namespace App\Services;

use App\Models\ApprovalLevel;
use App\Models\Loan;
use App\Models\User;
use App\Support\WorkflowSteps;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanWorkflowService
{
    public const STEPS = WorkflowSteps::ROLES;

    public function __construct(private RepaymentScheduleService $repayments) {}

    public function process(Loan $loan, string $action, array $data = []): Loan
    {
        return DB::transaction(function () use ($loan, $action, $data) {
            $user = Auth::user();

            match ($action) {
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
                'rollback_step' => $this->rollbackStep($loan, $user, $data),
                default => throw new \InvalidArgumentException("Unknown action: {$action}"),
            };

            DashboardStatsService::flushForUser($user->id);

            return $loan->fresh(['applicant', 'businessDetails', 'approvalLevels.user', 'loanPayments']);
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

    protected function forward(Loan $loan, User $user, int $nextStep, string $action, array $data): void
    {
        $this->logAction($loan, $user, $loan->current_step, $action, $data);
        $loan->update([
            'current_step' => $nextStep,
            'status' => 'in_review',
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
        $disbursedAmount = (float) $loan->proposed_amount;

        if ($disbursedAmount <= 0) {
            throw new \InvalidArgumentException('Cannot disburse a loan without a proposed amount.');
        }

        $gracePeriodMonths = (int) ($data['grace_period_months'] ?? config('wdf.grace_period_months', 3));

        $this->logAction($loan, $user, 9, 'disbursed', $data);
        $loan->update([
            'disbursed_amount' => $disbursedAmount,
            'date_issued' => now()->toDateString(),
            'status' => 'disbursed',
            'current_step' => 9,
        ]);

        $fresh = $loan->fresh();
        $existingPayment = $fresh->loanPayments()->orderBy('id')->first();

        if ($existingPayment) {
            $existingPayment->update([
                'amount_disbursed' => $disbursedAmount,
                'amount_requested' => $fresh->requested_amount,
            ]);
        } else {
            $this->repayments->createForLoan($fresh, $disbursedAmount, $gracePeriodMonths);
        }
    }

    protected function rollbackStep(Loan $loan, User $user, array $data): void
    {
        if ($loan->status === 'disbursed') {
            throw new \InvalidArgumentException('Cannot rollback a disbursed loan.');
        }

        [$previousStep, $status] = $this->rollbackTarget($loan);

        $action = ($loan->current_step === 1 && $loan->status === 'received')
            ? 'rolled_back_to_applicant'
            : 'rolled_back';

        $this->logAction($loan, $user, $loan->current_step, $action, $data);

        $updates = [
            'current_step' => $previousStep,
            'status' => $status,
        ];

        if ($loan->current_step === 8) {
            $updates['officer_id'] = null;
        }

        if ($loan->current_step === 7) {
            $updates['approved_by'] = null;
        }

        if ($loan->current_step === 3) {
            $updates['applicant_acceptance'] = 'pending';
        }

        $loan->update($updates);
    }

    protected function rollbackTarget(Loan $loan): array
    {
        if ($loan->current_step === 1 && $loan->status === 'received') {
            return [1, 'pending'];
        }

        return match ($loan->current_step) {
            2 => [1, 'received'],
            3 => [2, 'in_review'],
            4 => [3, 'awaiting_applicant'],
            5 => [4, 'in_review'],
            6 => [5, 'in_review'],
            7 => [6, 'in_review'],
            8 => [7, 'in_review'],
            9 => [8, 'approved'],
            default => throw new \InvalidArgumentException('This loan cannot be rolled back.'),
        };
    }
}
