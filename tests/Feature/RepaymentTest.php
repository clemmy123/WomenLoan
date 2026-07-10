<?php

namespace Tests\Feature;

use App\Models\LoanPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_disbursement_creates_repayment_schedule_with_interest(): void
    {
        $loan = $this->loanByTrack('WL000010');

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'disburse',
                'grace_period_months' => 3,
            ])
            ->assertRedirect();

        $payment = LoanPayment::where('loan_id', $loan->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(608000.0, (float) $payment->interest_amount);
        $this->assertSame(4408000.0, (float) $payment->outstanding_debt);
        $this->assertTrue($payment->isInGracePeriod());

        $schedule = app(\App\Services\RepaymentScheduleService::class)->installmentSchedule($payment);
        $this->assertCount(12, $schedule);
        $this->assertSame(367333.33, (float) $schedule[0]['amount_due']);
        $this->assertSame(
            now()->startOfDay()->addMonths(3)->toDateString(),
            $schedule[0]['due_date']
        );
    }

    public function test_applicant_can_record_payment(): void
    {
        $loan = $this->loanByTrack('WL000011');
        $payment = LoanPayment::withoutGlobalScopes()->where('loan_id', $loan->id)->firstOrFail();
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        $before = (float) $payment->outstanding_debt;

        $response = $this->actingAs($user)
            ->post(route('repayments.pay', $payment), [
                'amount' => 200000,
                'method' => 'M-Pesa',
            ]);

        $payment->refresh();
        $transactions = app(\App\Services\RepaymentScheduleService::class)->transactions($payment);
        $transactionIndex = count($transactions) - 1;
        $tx = $transactions[$transactionIndex];

        $response->assertRedirect(route('repayments.receipt', [$payment, $transactionIndex]));
        $response->assertSessionHas('success');
        $this->assertSame($before - 200000.0, (float) $payment->outstanding_debt);
        $this->assertNotEmpty($tx['reference']);
        $this->assertStringStartsWith('WDF-', $tx['reference']);
        $this->assertSame('M-Pesa', $tx['method']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $tx['date']);
    }

    public function test_accountant_cannot_record_payment(): void
    {
        $loan = $this->loanByTrack('WL000011');
        $payment = LoanPayment::withoutGlobalScopes()->where('loan_id', $loan->id)->firstOrFail();

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->post(route('repayments.pay', $payment), [
                'amount' => 200000,
                'method' => 'Bank Transfer',
            ])
            ->assertForbidden();
    }

    public function test_applicant_cannot_record_payment_during_grace_period(): void
    {
        $loan = $this->loanByTrack('WL000010');

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'disburse',
                'grace_period_months' => 3,
            ])
            ->assertRedirect();

        $payment = LoanPayment::where('loan_id', $loan->id)->firstOrFail();
        $user = \App\Models\User::findOrFail($loan->user_id);

        $this->actingAs($user)
            ->post(route('repayments.pay', $payment), [
                'amount' => 200000,
                'method' => 'Bank Transfer',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0.0, (float) $payment->fresh()->amount_paid);
    }

    public function test_repayments_index_shows_summary_search_and_list(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('repayments.index'))
            ->assertOk()
            ->assertSee(__('repayments.payments_title'), false)
            ->assertSee(__('repayments.index_subtitle'), false)
            ->assertSee(__('repayments.summary_title'), false)
            ->assertSee(__('repayments.list_title'), false)
            ->assertSee(__('repayments.search_placeholder'), false)
            ->assertSee(__('repayments.export_excel'), false)
            ->assertSee('WL000011', false);
    }

    public function test_repayments_index_can_search_by_track_id(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('repayments.index', ['search' => 'WL000011']))
            ->assertOk()
            ->assertSee('WL000011', false);
    }

    public function test_ministry_can_export_repayments_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('repayments.export.excel'))
            ->assertOk();

        $this->get(route('repayments.export.pdf'))
            ->assertOk();
    }

    public function test_applicant_sees_start_payment_alert_after_grace(): void
    {
        $loan = $this->loanByTrack('WL000011');
        $payment = LoanPayment::withoutGlobalScopes()->where('loan_id', $loan->id)->firstOrFail();
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('repayments.show', $payment))
            ->assertOk()
            ->assertSee(__('repayments.start_payment_title'), false)
            ->assertSee(__('repayments.payment_number', ['number' => 1]), false)
            ->assertSee(__('repayments.summary_title'), false)
            ->assertSee(__('repayments.pay_here'), false)
            ->assertSee('01J1027640100');
    }

    public function test_receipt_qr_payload_includes_full_payment_details(): void
    {
        $loan = $this->loanByTrack('WL000011');
        $payment = LoanPayment::withoutGlobalScopes()->where('loan_id', $loan->id)->firstOrFail();
        $tx = app(\App\Services\RepaymentScheduleService::class)->transactions($payment)[0];
        $receiptNumber = $tx['receipt_number'] ?? 'RCP-WL000011-001';

        $payload = app(\App\Services\ReceiptQrCodeService::class)->payload($payment, $tx, $receiptNumber);

        $this->assertStringContainsString('WDF LOAN PAYMENT RECEIPT', $payload);
        $this->assertStringContainsString('Payment DateTime =', $payload);
        $this->assertStringContainsString('Loan Track = WL000011', $payload);
        $this->assertStringContainsString('Payer Name =', $payload);
        $this->assertStringContainsString('Amount TZS =', $payload);
        $this->assertStringContainsString('Payment Ref =', $payload);
        $this->assertStringContainsString('WDF Account = 01J1027640100', $payload);
        $this->assertDoesNotMatchRegularExpression('/Payer Phone = \d{9,}/', $payload);
    }

    public function test_applicant_can_view_payment_receipt(): void
    {
        $loan = $this->loanByTrack('WL000011');
        $payment = LoanPayment::withoutGlobalScopes()->where('loan_id', $loan->id)->firstOrFail();
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('repayments.receipt', [$payment, 0]))
            ->assertOk()
            ->assertSee(__('repayments.receipt_title'), false)
            ->assertSee($payment->loan->loan_track_id, false)
            ->assertSee('WDF-WL000011-001', false)
            ->assertSee(__('repayments.payment_date'), false)
            ->assertSee(__('repayments.qr_caption'), false)
            ->assertSee('data:image/png;base64,', false);
    }

    public function test_staff_cannot_see_applicant_payment_actions(): void
    {
        $loan = $this->loanByTrack('WL000011');
        $payment = LoanPayment::withoutGlobalScopes()->where('loan_id', $loan->id)->firstOrFail();

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('repayments.show', $payment))
            ->assertOk()
            ->assertDontSee(__('repayments.start_payment_title'), false)
            ->assertDontSee(__('repayments.pay_here'), false)
            ->assertDontSee('01J1027640100')
            ->assertDontSee(__('repayments.accepted_methods'), false)
            ->assertDontSee(__('repayments.submit_payment'), false);
    }
}
