<?php

namespace App\Http\Controllers;

use App\Exports\RepaymentsExport;
use App\Http\Requests\RecordRepaymentRequest;
use App\Models\LoanPayment;
use App\Services\ReceiptQrCodeService;
use App\Services\RepaymentIndexService;
use App\Services\RepaymentScheduleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LoanPaymentController extends Controller
{
    public function __construct(
        private RepaymentScheduleService $schedule,
        private RepaymentIndexService $indexService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('view repayments');

        $filters = $this->indexService->filters($request);
        $payments = $this->indexService->paginate($filters);
        $summary = $this->indexService->summary($filters);
        $statusOptions = $this->indexService->statusOptions();
        $sortOptions = $this->indexService->sortOptions();

        return view('repayments.index', [
            'payments' => $payments,
            'summary' => $summary,
            'search' => $filters['search'],
            'status' => $filters['status'],
            'sort' => $filters['sort'],
            'statusOptions' => $statusOptions,
            'sortOptions' => $sortOptions,
            'indexService' => $this->indexService,
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view repayments');

        $filters = $this->indexService->filters($request);
        $summary = $this->indexService->summary($filters);
        $rows = $this->indexService->exportRows($filters);

        return Excel::download(
            new RepaymentsExport($summary, $rows, $filters),
            'wdf-repayments-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('view repayments');

        $filters = $this->indexService->filters($request);
        $summary = $this->indexService->summary($filters);
        $rows = $this->indexService->exportRows($filters);
        $statusLabel = $this->indexService->statusOptions()[$filters['status']] ?? $filters['status'];

        return Pdf::loadView('repayments.export-pdf', compact('summary', 'rows', 'filters', 'statusLabel'))
            ->download('wdf-repayments-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function show(LoanPayment $payment)
    {
        $this->authorize('view repayments');

        $payment->load('loan.applicant');
        $transactions = $this->schedule->transactions($payment);
        $account = config('wdf.repayment_account');
        $nextInstallment = $this->schedule->nextInstallment($payment);
        $suggestedAmount = (float) ($nextInstallment['amount_due'] ?? $this->schedule->monthlyInstallmentAmount($payment));
        $inGracePeriod = $payment->isInGracePeriod();
        $graceEndsAt = $payment->graceEndsAt();
        $isLoanApplicant = auth()->user()?->hasRole('applicant')
            && (int) $payment->loan?->user_id === (int) auth()->id();
        $canRecordPayment = $isLoanApplicant
            && auth()->user()?->can('record repayment')
            && ! $inGracePeriod
            && (float) $payment->outstanding_debt > 0;
        $paymentMethods = config('wdf.payment_methods', []);

        return view('repayments.show', [
            'payment' => $payment,
            'transactions' => $transactions,
            'account' => $account,
            'suggestedAmount' => $suggestedAmount,
            'nextInstallment' => $nextInstallment,
            'inGracePeriod' => $inGracePeriod,
            'graceEndsAt' => $graceEndsAt,
            'isLoanApplicant' => $isLoanApplicant,
            'canRecordPayment' => $canRecordPayment,
            'paymentMethods' => $paymentMethods,
            'indexService' => $this->indexService,
        ]);
    }

    public function pay(RecordRepaymentRequest $request, LoanPayment $payment): RedirectResponse
    {
        if ($payment->isInGracePeriod()) {
            return back()->with('error', __('repayments.still_in_grace'));
        }

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
        $receiptNumber = $tx['receipt_number'] ?? sprintf(
            'RCP-%s-%03d',
            $payment->loan?->loan_track_id ?? $payment->id,
            $transaction + 1
        );
        $qrCodeDataUri = app(ReceiptQrCodeService::class)->dataUri($payment, $tx, $receiptNumber);

        return view('repayments.receipt', compact('payment', 'tx', 'transaction', 'receiptNumber', 'qrCodeDataUri'));
    }
}
