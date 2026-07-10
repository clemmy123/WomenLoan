<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use Carbon\Carbon;

class RepaymentScheduleService
{
    public function createForLoan(Loan $loan, float $disbursedAmount, ?int $gracePeriodMonths = null): LoanPayment
    {
        $months = (int) config('wdf.repayment_term_months', 12);
        $graceMonths = $gracePeriodMonths ?? (int) config('wdf.grace_period_months', 3);
        $graceMonths = max(0, $graceMonths);
        $rate = (float) config('wdf.interest_rate', 0.16);
        $interest = round($disbursedAmount * $rate, 2);
        $totalPayable = round($disbursedAmount + $interest, 2);
        $monthlyAmount = $months > 0 ? round($totalPayable / $months, 2) : $totalPayable;

        $startDate = Carbon::parse($loan->date_issued ?? now())->startOfDay();
        $repaymentStart = $startDate->copy()->addMonths($graceMonths);
        $endDate = $repaymentStart->copy()->addMonths(max(0, $months - 1));
        $gracePeriodDays = $startDate->diffInDays($repaymentStart);

        return LoanPayment::create([
            'loan_id' => $loan->id,
            'amount_requested' => $loan->requested_amount,
            'amount_disbursed' => $disbursedAmount,
            'interest_amount' => $interest,
            'amount_paid' => 0,
            'outstanding_debt' => $totalPayable,
            'grace_period_days' => $gracePeriodDays,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'payment_interval' => 'monthly',
            'notes' => __('repayments.schedule_note', [
                'rate' => (int) ($rate * 100),
                'grace' => $graceMonths,
            ]),
            'payment_history' => [
                'installments' => $this->buildInstallments($repaymentStart, $months, $monthlyAmount, $totalPayable),
                'transactions' => [],
            ],
        ]);
    }

    public function recordPayment(LoanPayment $payment, float $amount, ?string $method = null): array
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return ['transaction_index' => null];
        }

        $outstanding = (float) $payment->outstanding_debt;
        $applied = min($amount, $outstanding);

        $history = $this->normalizeHistory($payment->payment_history);
        $remaining = $applied;

        foreach ($history['installments'] as $index => $installment) {
            if ($remaining <= 0) {
                break;
            }

            $due = (float) ($installment['amount_due'] ?? 0);
            $paid = (float) ($installment['amount_paid'] ?? 0);
            $left = max(0, $due - $paid);

            if ($left <= 0) {
                continue;
            }

            $pay = min($remaining, $left);
            $newPaid = round($paid + $pay, 2);
            $history['installments'][$index]['amount_paid'] = $newPaid;
            $history['installments'][$index]['status'] = $newPaid >= $due ? 'paid' : 'partial';
            $remaining -= $pay;
        }

        $sequence = count($history['transactions']) + 1;
        $trackId = $payment->loan?->loan_track_id ?? $payment->id;
        $receiptNumber = sprintf('RCP-%s-%03d', $trackId, $sequence);
        $reference = sprintf('WDF-%s-%03d', $trackId, $sequence);

        $history['transactions'][] = [
            'date' => now()->toDateTimeString(),
            'amount' => $applied,
            'reference' => $reference,
            'method' => $method ?? 'Bank Transfer',
            'receipt_number' => $receiptNumber,
            'outstanding_before' => $outstanding,
            'outstanding_after' => round(max(0, $outstanding - $applied), 2),
        ];

        $transactionIndex = count($history['transactions']) - 1;

        $payment->update([
            'payment_history' => $history,
            'amount_paid' => round((float) $payment->amount_paid + $applied, 2),
            'outstanding_debt' => round(max(0, $outstanding - $applied), 2),
        ]);

        return [
            'transaction_index' => $transactionIndex,
            'receipt_number' => $receiptNumber,
            'reference' => $reference,
            'amount' => $applied,
        ];
    }

    public function nextInstallment(LoanPayment $payment): ?array
    {
        foreach ($this->installmentSchedule($payment) as $installment) {
            $status = $installment['status'] ?? 'pending';
            if (in_array($status, ['pending', 'partial'], true)) {
                return $installment;
            }
        }

        return null;
    }

    public function installmentSchedule(LoanPayment $payment): array
    {
        return $this->normalizeHistory($payment->payment_history)['installments'];
    }

    public function transactions(LoanPayment $payment): array
    {
        return $this->normalizeHistory($payment->payment_history)['transactions'];
    }

    public function monthlyInstallmentAmount(LoanPayment $payment): float
    {
        $schedule = $this->installmentSchedule($payment);

        return (float) ($schedule[0]['amount_due'] ?? 0);
    }

    public function totalPayable(LoanPayment $payment): float
    {
        return (float) $payment->amount_disbursed + (float) $payment->interest_amount;
    }

    protected function normalizeHistory(?array $history): array
    {
        if (! $history) {
            return ['installments' => [], 'transactions' => []];
        }

        if (isset($history['installments'])) {
            return [
                'installments' => $history['installments'],
                'transactions' => $history['transactions'] ?? [],
            ];
        }

        if (isset($history[0]['installment'])) {
            return [
                'installments' => $history,
                'transactions' => [],
            ];
        }

        return ['installments' => [], 'transactions' => $history];
    }

    protected function buildInstallments(Carbon $firstDueDate, int $months, float $monthlyAmount, float $totalPayable): array
    {
        $installments = [];
        $allocated = 0.0;

        for ($i = 1; $i <= $months; $i++) {
            $due = $i === $months
                ? round($totalPayable - $allocated, 2)
                : $monthlyAmount;

            $allocated += $due;

            $installments[] = [
                'installment' => $i,
                'due_date' => $firstDueDate->copy()->addMonths($i - 1)->toDateString(),
                'amount_due' => $due,
                'amount_paid' => 0,
                'status' => 'pending',
            ];
        }

        return $installments;
    }
}
