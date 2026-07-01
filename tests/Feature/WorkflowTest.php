<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_ward_receives_pending_application(): void
    {
        $loan = $this->loanByTrack('WL000001');

        $response = $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'receive',
                'comments' => 'Received at ward.',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame('received', $loan->status);
        $this->assertSame(1, $loan->current_step);
    }

    public function test_ward_forwards_received_application_to_ministry(): void
    {
        $loan = $this->loanByTrack('WL000002');

        $response = $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_ministry',
                'comments' => 'Forward to ministry.',
            ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame(2, $loan->current_step);
        $this->assertSame('in_review', $loan->status);
    }

    public function test_ministry_proposes_amount_and_redirects_when_loan_leaves_scope(): void
    {
        $loan = $this->loanByTrack('WL000003');

        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'propose_amount',
                'proposed_amount' => 7500000,
                'comments' => 'Proposed amount.',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame(3, $loan->current_step);
        $this->assertSame('awaiting_applicant', $loan->status);
        $this->assertSame('7500000.00', $loan->proposed_amount);
    }

    public function test_applicant_accepts_proposed_amount(): void
    {
        $loan = $this->loanByTrack('WL000004');

        $response = $this->actingAsRole('applicant9@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'accept_amount',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame(4, $loan->current_step);
        $this->assertSame('accepted', $loan->applicant_acceptance);
    }

    public function test_ministry_forwards_to_assistant_director_at_step_four(): void
    {
        $loan = $this->loanByTrack('WL000005');

        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_ass_dir',
                'comments' => 'To assistant director.',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $loan->refresh();
        $this->assertSame(5, $loan->current_step);
    }

    public function test_assistant_director_forwards_to_director(): void
    {
        $loan = $this->loanByTrack('WL000006');

        $response = $this->actingAsRole('assdir@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_director',
                'comments' => 'Recommend approval.',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $loan->refresh();
        $this->assertSame(6, $loan->current_step);
    }

    public function test_director_forwards_to_km(): void
    {
        $loan = $this->loanByTrack('WL000007');

        $response = $this->actingAsRole('director@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_km',
                'comments' => 'Endorsed.',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $loan->refresh();
        $this->assertSame(7, $loan->current_step);
    }

    public function test_km_approves_loan_without_null_proposed_amount_error(): void
    {
        $loan = $this->loanByTrack('WL000008');

        $response = $this->actingAsRole('km@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'approve_km',
                'comments' => 'Approved.',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame(8, $loan->current_step);
        $this->assertSame('approved', $loan->status);
        $this->assertDatabaseHas('approval_levels', [
            'loan_id' => $loan->id,
            'step_number' => 7,
            'action_taken' => 'approved',
        ]);
    }

    public function test_chief_assigns_accountant(): void
    {
        $loan = $this->loanByTrack('WL000009');
        $accountant = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $response = $this->actingAsRole('chief@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'assign_accountant',
                'accountant_id' => $accountant->id,
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $loan->refresh();
        $this->assertSame(9, $loan->current_step);
        $this->assertSame($accountant->id, $loan->officer_id);
        $this->assertSame('ready_for_disbursement', $loan->status);
    }

    public function test_accountant_disburses_assigned_loan(): void
    {
        $loan = $this->loanByTrack('WL000010');

        $response = $this->actingAsRole('accountant1@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'disburse',
                'disbursed_amount' => 3800000,
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $loan->refresh();
        $this->assertSame('disbursed', $loan->status);
        $this->assertSame('3800000.00', $loan->disbursed_amount);
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'amount_disbursed' => 3800000,
        ]);
    }

    public function test_ministry_can_rollback_application_to_previous_step(): void
    {
        $loan = $this->loanByTrack('WL000005');

        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'rollback_step',
                'comments' => 'Incorrect supporting documents.',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $loan->refresh();
        $this->assertSame(3, $loan->current_step);
        $this->assertSame('awaiting_applicant', $loan->status);
    }

    public function test_unauthorized_workflow_action_is_forbidden(): void
    {
        $loan = $this->loanByTrack('WL000001');

        $response = $this->actingAsRole('km@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'receive',
            ]);

        $response->assertForbidden();
    }

    public function test_invalid_loan_hash_returns_not_found(): void
    {
        $this->actingAsRole('admin@wdf.go.tz')
            ->post('/loans/invalidhash/workflow', ['action' => 'receive'])
            ->assertNotFound();
    }
}
