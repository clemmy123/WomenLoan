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
            ])
            ->assertRedirect();

        $payment = LoanPayment::where('loan_id', $loan->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(608000.0, (float) $payment->interest_amount);
        $this->assertSame(4408000.0, (float) $payment->outstanding_debt);

        $schedule = app(\App\Services\RepaymentScheduleService::class)->installmentSchedule($payment);
        $this->assertCount(12, $schedule);
        $this->assertSame(367333.33, (float) $schedule[0]['amount_due']);
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
                'reference' => 'TXN12345',
            ]);

        $payment->refresh();
        $transactionIndex = count(app(\App\Services\RepaymentScheduleService::class)->transactions($payment)) - 1;

        $response->assertRedirect(route('repayments.receipt', [$payment, $transactionIndex]));
        $response->assertSessionHas('success');
        $payment->refresh();
        $this->assertSame($before - 200000.0, (float) $payment->outstanding_debt);
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
            ->assertSee($payment->loan->loan_track_id, false);
    }

    public function test_repayment_show_displays_collection_account(): void
    {
        $loan = $this->loanByTrack('WL000011');
        $payment = LoanPayment::withoutGlobalScopes()->where('loan_id', $loan->id)->firstOrFail();

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('repayments.show', $payment))
            ->assertOk()
            ->assertSee(config('wdf.repayment_account.account_number'))
            ->assertSee(__('repayments.pay_here'), false);
    }
}
