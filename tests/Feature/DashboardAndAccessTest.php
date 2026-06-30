<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_applicant_dashboard_loads_without_timeout(): void
    {
        $response = $this->actingAsRole('test@example.com')
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('dashboard.overview'), false);
    }

    public function test_applicant_can_view_own_loan_applications_index(): void
    {
        $response = $this->actingAsRole('test@example.com')
            ->get(route('loan-applications.index'));

        $response->assertOk();
    }

    public function test_applicant_can_open_loan_show_page(): void
    {
        $loan = $this->loanByTrack('WL000001');
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();
        $loan->update(['user_id' => $user->id, 'applicant_id' => $user->applicant?->id]);

        $loan->businessDetails()->update([
            'business_proposal_document' => 'proposals/sample.pdf',
            'business_registration_attachment' => 'registrations/sample.pdf',
        ]);

        $response = $this->actingAs($user)
            ->get(route('loan-applications.show', $loan->hashid));

        $response->assertOk();
        $response->assertSee(__('loans.applicant_information'), false);
        $response->assertSee(__('loans.supporting_documents'), false);
        $response->assertSee(__('loans.guarantor_information'), false);
        $response->assertSee(__('loans.bank_details'), false);
        $response->assertSee('sample.pdf');
        $response->assertSee($loan->businessDetails->business_name);
        $response->assertSee('Guarantor 1');
        $response->assertSee('CRDB Bank');
    }

    public function test_ministry_can_view_reports(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_track_loan_by_track_id(): void
    {
        $this->actingAsRole('applicant9@wdf.go.tz')
            ->get(route('loans.track', ['track_id' => 'WL000004']))
            ->assertOk()
            ->assertSee('WL000004');
    }
}
