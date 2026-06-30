<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApplicationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_applicant_without_active_loan_can_open_apply_form(): void
    {
        $this->actingAsRole('test@example.com')
            ->get(route('loan-applications.create'))
            ->assertOk();
    }

    public function test_applicant_with_active_loan_cannot_open_apply_form(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $loan = $this->loanByTrack('WL000001');

        $loan->update([
            'user_id' => $user->id,
            'applicant_id' => $user->applicant?->id,
        ]);

        $this->actingAs($user)
            ->get(route('loan-applications.create'))
            ->assertRedirect(route('loan-applications.index'))
            ->assertSessionHasErrors('error');
    }

    public function test_applicant_with_only_disbursed_loan_can_apply_again(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $loan = $this->loanByTrack('WL000011');

        $loan->update([
            'user_id' => $user->id,
            'applicant_id' => $user->applicant?->id,
            'status' => 'disbursed',
        ]);

        $this->actingAs($user)
            ->get(route('loan-applications.create'))
            ->assertOk();
    }

    public function test_apply_index_hides_start_new_when_active_loan_exists(): void
    {
        $user = User::where('email', 'applicant6@wdf.go.tz')->firstOrFail();

        $this->actingAs($user)
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertDontSee(__('loans.start_new'), false);
    }
}
