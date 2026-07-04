<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordRepaymentRequest;
use App\Models\LoanPayment;
use App\Services\RepaymentScheduleService;
use Illuminate\Http\RedirectResponse;

class LoanPaymentController extends Controller
{
    public function __construct(private RepaymentScheduleService $schedule) {}

    public function index()
    {
        $this->authorize('view repayments');

        $payments = LoanPayment::with('loan.applicant')->latest()->paginate(20);

        return view('repayments.index', compact('payments'));
    }

    public function show(LoanPayment $payment)
    {
        $this->authorize('view repayments');

        $payment->load('loan.applicant');
        $transactions = $this->schedule->transactions($payment);
        $account = config('wdf.repayment_account');
        $suggestedAmount = $this->schedule->monthlyInstallmentAmount($payment);

        return view('repayments.show', compact('payment', 'transactions', 'account', 'suggestedAmount'));
    }

    public function pay(RecordRepaymentRequest $request, LoanPayment $payment): RedirectResponse
    {
        if ((float) $payment->outstanding_debt <= 0) {
            return back()->with('error', __('repayments.already_cleared'));
        }

        $amount = (float) $request->input('amount');
        if ($amount > (float) $payment->outstanding_debt) {
            return back()
                ->withInput()
                ->with('error', __('repayments.amount_exceeds_outstanding'));
        }

        $result = $this->schedule->recordPayment(
            $payment,
            $amount,
            $request->input('reference'),
            $request->input('method'),
        );

        if ($result['transaction_index'] === null) {
            return back()->with('error', __('repayments.already_cleared'));
        }

        return redirect()
            ->route('repayments.receipt', [
                'payment' => $payment,
                'transaction' => $result['transaction_index'],
            ])
            ->with('success', __('repayments.payment_recorded'));
    }

    public function receipt(LoanPayment $payment, int $transaction)
    {
        $this->authorize('view repayments');

        $payment->load('loan.applicant');
        $transactions = $this->schedule->transactions($payment->fresh());

        if (! isset($transactions[$transaction])) {
            abort(404);
        }

        $tx = $transactions[$transaction];

        return view('repayments.receipt', compact('payment', 'tx', 'transaction'));
    }
}
