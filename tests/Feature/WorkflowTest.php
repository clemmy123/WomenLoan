<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_ward_cannot_receive_application(): void
    {
        $loan = $this->loanByTrack('WL000002');

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'receive',
                'comments' => 'Should not be allowed.',
            ])
            ->assertForbidden();
    }

    public function test_ward_forwards_received_application_to_ministry(): void
    {
        $loan = $this->loanByTrack('WL000002');
        $applicantName = $loan->applicant()->withoutGlobalScopes()->value('full_name');

        $response = $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_ministry',
                'comments' => 'Forward to ministry.',
                'attachment' => UploadedFile::fake()->create('supervision.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame(2, $loan->current_step);
        $this->assertSame('in_review', $loan->status);
        $this->assertDatabaseHas('approval_levels', [
            'loan_id' => $loan->id,
            'action_taken' => 'forwarded_to_ministry',
        ]);

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertSee($applicantName, false)
            ->assertSee($loan->businessDetails->business_name, false);
    }

    public function test_ward_cdo_sees_all_applications_from_their_ward(): void
    {
        $this->actingAsRole('ward.cdo@wdf.go.tz');

        $this->assertTrue(Loan::where('loan_track_id', 'WL000001')->exists());
        $this->assertTrue(Loan::where('loan_track_id', 'WL000003')->exists());
    }

    public function test_forward_ministry_requires_supervision_document(): void
    {
        $loan = $this->loanByTrack('WL000002');

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_ministry',
                'comments' => 'Missing attachment.',
            ])
            ->assertSessionHasErrors('attachment');
    }

    public function test_forward_ass_dir_requires_committee_minutes(): void
    {
        $loan = $this->loanByTrack('WL000005');

        $this->actingAsRole('ministry@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_ass_dir',
                'comments' => 'Missing minutes.',
            ])
            ->assertSessionHasErrors('attachment');
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
                'comments' => 'I accept the proposed amount.',
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

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertSee(__('workflow.buttons.submit'), false)
            ->assertDontSee("modal = 'propose_amount'", false);

        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_ass_dir',
                'comments' => 'To assistant director.',
                'attachment' => UploadedFile::fake()->create('committee-minutes.pdf', 100, 'application/pdf'),
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
                'comments' => 'Assigning disbursement officer.',
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
                'grace_period_months' => 3,
                'comments' => 'Funds disbursed.',
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

    public function test_disburse_syncs_existing_payment_ledger_to_actual_amount(): void
    {
        $loan = $this->loanByTrack('WL000010');

        \App\Models\LoanPayment::create([
            'loan_id' => $loan->id,
            'amount_requested' => $loan->requested_amount,
            'amount_disbursed' => 1000,
            'interest_amount' => 160,
            'amount_paid' => 0,
            'outstanding_debt' => 1160,
            'grace_period_days' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'payment_interval' => 'monthly',
        ]);

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'disburse',
                'grace_period_months' => 3,
                'comments' => 'Funds disbursed.',
            ])
            ->assertRedirect(route('loan-applications.show', $loan->hashid));

        $loan->refresh();
        $this->assertSame('3800000.00', $loan->disbursed_amount);
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'amount_disbursed' => 3800000,
        ]);
        $this->assertSame(1, $loan->loanPayments()->count());
    }

    public function test_accountant_cannot_disburse_custom_amount(): void
    {
        $loan = $this->loanByTrack('WL000010');

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'disburse',
                'grace_period_months' => 3,
                'disbursed_amount' => 1000000,
                'comments' => 'Attempt custom amount.',
            ])
            ->assertSessionHasErrors('disbursed_amount');
    }

    public function test_disburse_button_hidden_after_loan_is_disbursed(): void
    {
        $loan = $this->loanByTrack('WL000011');

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertDontSee("modal = 'disburse'", false);
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
        $this->assertDatabaseHas('approval_levels', [
            'loan_id' => $loan->id,
            'action_taken' => 'rolled_back',
        ]);
    }

    public function test_ward_cdo_can_rollback_received_application_to_applicant(): void
    {
        $loan = $this->loanByTrack('WL000002');

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertSee(__('workflow.buttons.rollback_to_applicant'), false);

        $response = $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'rollback_step',
                'comments' => 'Missing guarantor documents.',
            ]);

        $response->assertRedirect(route('loan-applications.show', $loan->hashid));
        $loan->refresh();
        $loan->load('user');
        $this->assertSame(1, $loan->current_step);
        $this->assertSame('pending', $loan->status);
        $owner = $loan->user;
        $this->assertNotNull($owner);
        $this->assertTrue($loan->isEditableByApplicant($owner));
        $this->assertDatabaseHas('approval_levels', [
            'loan_id' => $loan->id,
            'action_taken' => 'rolled_back_to_applicant',
        ]);

        $this->actingAs($owner)
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertSee(__('loans.edit_application'), false);
    }

    public function test_workflow_action_requires_comments(): void
    {
        $loan = $this->loanByTrack('WL000008');

        $this->actingAsRole('km@wdf.go.tz')
            ->from(route('loan-applications.show', $loan->hashid))
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'approve_km',
            ])
            ->assertRedirect(route('loan-applications.show', $loan->hashid))
            ->assertSessionHasErrors('comments');
    }

    public function test_km_can_rollback_before_approval_but_not_after(): void
    {
        $atKm = $this->loanByTrack('WL000008');

        $this->actingAsRole('km@wdf.go.tz')
            ->get(route('loan-applications.show', $atKm->hashid))
            ->assertOk()
            ->assertSee("modal = 'rollback_step'", false);

        $this->actingAsRole('km@wdf.go.tz')
            ->post(route('loans.workflow', $atKm->hashid), [
                'action' => 'rollback_step',
                'comments' => 'Needs director clarification.',
            ])
            ->assertRedirect(route('loan-applications.show', $atKm->hashid));

        $atKm->refresh();
        $this->assertSame(6, $atKm->current_step);
        $this->assertSame('in_review', $atKm->status);

        $approved = $this->loanByTrack('WL000009');

        $this->actingAsRole('km@wdf.go.tz')
            ->get(route('loan-applications.show', $approved->hashid))
            ->assertOk()
            ->assertDontSee("modal = 'rollback_step'", false);

        $this->actingAsRole('km@wdf.go.tz')
            ->post(route('loans.workflow', $approved->hashid), [
                'action' => 'rollback_step',
                'comments' => 'Should be blocked after approval.',
            ])
            ->assertForbidden();
    }

    public function test_director_loses_rollback_after_forwarding_to_km(): void
    {
        $loan = $this->loanByTrack('WL000008');

        $this->actingAsRole('director@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertDontSee("modal = 'rollback_step'", false);

        $this->actingAsRole('director@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'rollback_step',
                'comments' => 'Director should not rollback at KM step.',
            ])
            ->assertForbidden();
    }

    public function test_chief_and_accountant_cannot_rollback(): void
    {
        $approved = $this->loanByTrack('WL000009');
        $ready = $this->loanByTrack('WL000010');

        $this->actingAsRole('chief@wdf.go.tz')
            ->get(route('loan-applications.show', $approved->hashid))
            ->assertOk()
            ->assertDontSee("modal = 'rollback_step'", false);

        $this->actingAsRole('chief@wdf.go.tz')
            ->post(route('loans.workflow', $approved->hashid), [
                'action' => 'rollback_step',
                'comments' => 'Chief cannot rollback.',
            ])
            ->assertForbidden();

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->get(route('loan-applications.show', $ready->hashid))
            ->assertOk()
            ->assertDontSee("modal = 'rollback_step'", false);

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->post(route('loans.workflow', $ready->hashid), [
                'action' => 'rollback_step',
                'comments' => 'Accountant cannot rollback.',
            ])
            ->assertForbidden();
    }

    public function test_unauthorized_workflow_action_is_forbidden(): void
    {
        $loan = $this->loanByTrack('WL000001');

        $response = $this->actingAsRole('km@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'receive',
                'comments' => 'Unauthorized action.',
            ]);

        $response->assertForbidden();
    }

    public function test_invalid_loan_hash_returns_not_found(): void
    {
        $this->actingAsRole('admin@wdf.go.tz')
            ->post('/loans/invalidhash/workflow', [
                'action' => 'receive',
                'comments' => 'Invalid loan.',
            ])
            ->assertNotFound();
    }
}
