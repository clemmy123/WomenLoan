<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\DashboardStatsService;
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
        $staff = \App\Models\User::where('email', 'ward.cdo@wdf.go.tz')->firstOrFail();
        $loan->update([
            'user_id' => $user->id,
            'applicant_id' => $user->applicant?->id,
            'comments' => 'Internal staff note for applicant hide test',
            'approved_by' => 'Hidden Officer',
        ]);

        $loan->businessDetails()->update([
            'business_proposal_document' => 'proposals/sample.pdf',
            'business_registration_attachment' => 'registrations/sample.pdf',
        ]);

        \App\Models\ApprovalLevel::create([
            'loan_id' => $loan->id,
            'user_id' => $staff->id,
            'step_number' => 1,
            'action_taken' => 'forwarded_to_ministry',
            'proposed_amount' => 0,
            'attachment_path' => 'workflow/supervision-secret.pdf',
            'comments' => 'Secret supervision conversation',
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
        $response->assertSee('Guarantor');
        $response->assertSee('CRDB Bank');
        $response->assertDontSee(__('loans.progress_steps'), false);
        $response->assertDontSee(__('loans.approval_history'), false);
        $response->assertDontSee(__('workflow.supervision_document'), false);
        $response->assertDontSee('Internal staff note for applicant hide test');
        $response->assertDontSee('Secret supervision conversation');
        $response->assertDontSee('supervision-secret.pdf');
        $response->assertDontSee('Hidden Officer');
    }

    public function test_katibu_mkuu_sees_application_progress_tracker(): void
    {
        $loan = $this->loanByTrack('WL000001');
        $loan->update(['current_step' => 7]);

        $response = $this->actingAsRole('km@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid));

        $response->assertOk();
        $response->assertSee(__('loans.summary'), false);
        $response->assertSee(__('common.type'), false);
        $response->assertSee(loan_type_label($loan->loan_type), false);
        if ($loan->loan_type === 'group') {
            $response->assertSee(__('loans.loan_group'), false);
            $response->assertSee(__('groups.group_members'), false);
            foreach ($loan->group?->members ?? [] as $member) {
                $response->assertSee($member->full_name ?: $member->first_name, false);
            }
        } else {
            $response->assertSee(__('loans.applicant_name'), false);
            $response->assertSee(__('applicants.first_name'), false);
            $response->assertSee(__('applicants.last_name'), false);
        }
        $response->assertSee(__('loans.requested_amount_short'), false);
        $response->assertSee(__('loans.proposed_amount_short'), false);
        $response->assertSee(__('applicants.sex'), false);
        $response->assertSee(__('applicants.marital_status'), false);
        $response->assertSee(__('applicants.nationality'), false);
        $response->assertSee(__('loans.has_disability'), false);
        $response->assertSee(__('loans.is_widowed'), false);
        $response->assertSee(__('loans.business_name'), false);
        $response->assertSee(__('loans.business_phone'), false);
        $response->assertSee(__('loans.business_sector'), false);
        $response->assertSee(__('loans.business_type'), false);
        $response->assertSee(__('loans.tin_number'), false);
        $response->assertSee(__('loans.bank_name'), false);
        $response->assertSee(__('loans.bank_number'), false);
        $response->assertSee(__('loans.progress_steps'), false);
        $response->assertSee(__('loans.progress_steps_help'), false);
        $response->assertSee(__('loans.workflow_steps.1'), false);
        $response->assertSee(__('loans.workflow_steps.4'), false);
        $response->assertSee(__('loans.workflow_steps.6'), false);
        $response->assertSee(__('loans.progress_status.under_review'), false);
        $response->assertSee(__('loans.progress_status.completed'), false);
        $response->assertDontSee(__('loans.guarantor_information'), false);
        $response->assertDontSee(__('loans.supporting_documents'), false);
        $response->assertDontSee(__('loans.approval_history'), false);
    }

    public function test_katibu_mkuu_sees_group_member_names_on_group_loan(): void
    {
        $loan = $this->loanByTrack('WL000002');
        $group = \App\Models\LoanGroup::firstOrCreate(
            ['name' => 'KM Review Women Group'],
            [
                'registration_number' => 'WDF-GROUP-KM-001',
                'phone' => '255712399001',
                'email' => 'km.review.group@wdf.go.tz',
            ]
        );

        $memberNames = ['Asha Juma', 'Rehema Ally', 'Neema Hassan'];
        foreach ($memberNames as $index => $fullName) {
            [$first, $last] = explode(' ', $fullName, 2);
            \App\Models\LoanGroupMember::updateOrCreate(
                ['loan_group_id' => $group->id, 'nin' => '1990010112345'.str_pad((string) ($index + 1), 7, '0', STR_PAD_LEFT)],
                [
                    'first_name' => $first,
                    'middle_name' => null,
                    'last_name' => $last,
                    'full_name' => $fullName,
                    'phone' => '255713'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                    'sex' => 'Female',
                    'is_group_leader' => $index === 0,
                ]
            );
        }

        $loan->update([
            'current_step' => 7,
            'loan_type' => 'group',
            'loan_group_id' => $group->id,
        ]);

        $response = $this->actingAsRole('km@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid));

        $response->assertOk();
        $response->assertSee(__('loans.loan_group'), false);
        $response->assertSee('KM Review Women Group', false);
        $response->assertSee(__('groups.group_members'), false);
        $response->assertDontSee(__('loans.applicant_name'), false);

        foreach ($memberNames as $name) {
            $response->assertSee($name, false);
        }
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

    public function test_dashboard_stat_filter_shows_pending_loans_in_recent_list(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('dashboard', ['recent' => 'pending']))
            ->assertOk()
            ->assertSee(__('dashboard.recent_filter_pending'), false)
            ->assertSee('WL000001', false)
            ->assertDontSee('WL000011', false);
    }

    public function test_dashboard_stat_filter_shows_disbursed_loans_in_recent_list(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('dashboard', ['recent' => 'disbursed']))
            ->assertOk()
            ->assertSee(__('dashboard.recent_filter_disbursed'), false)
            ->assertSee('WL000011', false);
    }

    public function test_dashboard_recent_list_supports_search_and_sort(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('dashboard', [
                'search' => 'Tambukareli Women',
                'sort' => 'track_id',
            ]))
            ->assertOk()
            ->assertSee(__('dashboard.sort_by'), false)
            ->assertSee('Tambukareli Women Entrepreneurs', false)
            ->assertSee(__('dashboard.recent_search_placeholder'), false);
    }

    public function test_dashboard_excludes_loans_entered_before_current_fiscal_year(): void
    {
        $loan = $this->loanByTrack('WL000011');

        \Illuminate\Support\Facades\DB::table('loans')
            ->where('id', $loan->id)
            ->update(['created_at' => '2024-08-15 10:00:00']);

        $ministry = \App\Models\User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        DashboardStatsService::flushForUser($ministry->id);

        $this->actingAs($ministry);

        $stats = app(DashboardStatsService::class)->forUser();
        $this->assertSame(10, $stats['total']);
        $this->assertSame(0, $stats['disbursed']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>WL000011<', false);
    }

    public function test_dashboard_shows_current_fiscal_year_label(): void
    {
        $currentFy = app(DashboardStatsService::class)->currentFiscalYearKey();

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.fiscal_year_scope', ['year' => $currentFy]), false);
    }

    public function test_track_loan_by_track_id(): void
    {
        $this->actingAsRole('applicant9@wdf.go.tz')
            ->get(route('loans.track', ['track_id' => 'WL000004']))
            ->assertOk()
            ->assertSee('WL000004');
    }
}
