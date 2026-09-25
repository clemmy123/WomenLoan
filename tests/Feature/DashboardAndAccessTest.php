<?php

namespace Tests\Feature;

use App\Models\DraftLoan;
use App\Services\DashboardStatsService;
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
            'action_taken' => 'forwarded_to_council',
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

    public function test_assigned_officer_is_visible_from_ministry_level_only(): void
    {
        $loan = $this->loanByTrack('WL000001');

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertDontSee(__('loans.assigned_officer'), false);

        $this->actingAsRole('council.cdo@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertDontSee(__('loans.assigned_officer'), false);

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertSee(__('loans.assigned_officer'), false);

        $this->actingAsRole('km@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertSee(__('loans.assigned_officer'), false);
    }

    public function test_list_shows_workflow_step_from_ministry_level_only(): void
    {
        $assDirLabel = __('loans.workflow_steps.6');
        $wardLabel = __('loans.workflow_steps.1');
        $ministryLabel = __('loans.workflow_steps.3');

        $this->actingAsRole('assdir@wdf.go.tz')
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertSee($assDirLabel, false);

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertSee($ministryLabel, false);

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertDontSee($assDirLabel, false)
            ->assertDontSee($wardLabel, false);

        $this->actingAsRole('council.cdo@wdf.go.tz')
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertDontSee($assDirLabel, false);
    }

    public function test_katibu_mkuu_sees_application_progress_tracker(): void
    {
        $loan = $this->loanByTrack('WL000001');
        $loan->update(['current_step' => 8]);

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
        $response->assertSee(__('loans.assigned_officer'), false);
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

    public function test_workflow_step_labels_match_actual_roles(): void
    {
        app()->setLocale('en');
        $this->assertSame('Assistant Director Review', __('loans.workflow_steps.6'));
        $this->assertSame('Permanent Secretary Review', __('loans.workflow_steps.8'));
        $this->assertSame('Assistant Director', role_label('assistant_director'));
        $this->assertSame('Permanent Secretary', role_label('km'));
        $this->assertSame('Ass. Director', \App\Support\WorkflowSteps::labelForStep(6));
        $this->assertSame('Permanent Secretary', \App\Support\WorkflowSteps::labelForStep(8));
        $this->assertStringNotContainsString('Katibu Mkuu', __('loans.workflow_steps.6'));
        $this->assertStringNotContainsString('Katibu Mkuu', __('loans.workflow_steps.8'));

        app()->setLocale('sw');
        $this->assertSame('Ukaguzi wa Mkurugenzi Msaidizi', __('loans.workflow_steps.6'));
        $this->assertSame('Ukaguzi wa Katibu Mkuu', __('loans.workflow_steps.8'));
        $this->assertSame('Mkurugenzi Msaidizi', role_label('assistant_director'));
        $this->assertSame('Katibu Mkuu', role_label('km'));
        $this->assertSame('Mkurugenzi Msaidizi', \App\Support\WorkflowSteps::labelForStep(6));
        $this->assertSame('Katibu Mkuu', \App\Support\WorkflowSteps::labelForStep(8));
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
            'current_step' => 8,
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
            ->assertDontSee('WL000012', false);
    }

    public function test_dashboard_stat_filter_shows_disbursed_loans_in_recent_list(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('dashboard', ['recent' => 'disbursed']))
            ->assertOk()
            ->assertSee(__('dashboard.recent_filter_disbursed'), false)
            ->assertSee(__('dashboard.disbursed_summary_copy', [
                'all' => '1',
                'individual' => '0',
                'group' => '1',
            ]), false)
            ->assertSee(__('dashboard.summary_all'), false)
            ->assertSee(__('dashboard.summary_individual'), false)
            ->assertSee(__('dashboard.summary_groups'), false)
            ->assertSee(__('repayments.collection_rate', ['rate' => 22]), false)
            ->assertSee(__('repayments.disbursed_col'), false)
            ->assertSee(__('repayments.amount_paid_col'), false)
            ->assertSee(__('repayments.outstanding'), false)
            ->assertSee('WL000012', false)
            ->assertDontSee(__('common.actions'), false)
            ->assertDontSee(__('dashboard.funds_received'), false)
            ->assertSee(loan_status_label('disbursed'), false);
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
        $loan = $this->loanByTrack('WL000012');

        \Illuminate\Support\Facades\DB::table('loans')
            ->where('id', $loan->id)
            ->update(['created_at' => '2024-08-15 10:00:00']);

        $ministry = \App\Models\User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        DashboardStatsService::flushForUser($ministry->id);

        $this->actingAs($ministry);

        $stats = app(DashboardStatsService::class)->forUser();
        $this->assertSame(11, $stats['loan_count']);
        $this->assertSame($stats['individual_count'] + $stats['group_members_count'], $stats['total']);
        $this->assertSame(0, $stats['disbursed']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>WL000012<', false);
    }

    public function test_approved_stat_includes_disbursed_loans(): void
    {
        $ministry = \App\Models\User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        DashboardStatsService::flushForUser($ministry->id);

        $this->actingAs($ministry);

        $stats = app(DashboardStatsService::class)->forUser();

        $this->assertGreaterThanOrEqual($stats['disbursed'], $stats['approved_total']);
        $this->assertSame(
            $stats['approved'] + $stats['ready_for_disbursement'] + $stats['disbursed'],
            $stats['approved_total']
        );

        $this->get(route('dashboard', ['recent' => 'approved']))
            ->assertOk()
            ->assertSee('WL000012', false)
            ->assertDontSee(__('common.actions'), false);
    }

    public function test_total_applications_count_individuals_and_group_members(): void
    {
        $ministry = \App\Models\User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        DashboardStatsService::flushForUser($ministry->id);
        $this->actingAs($ministry);

        $stats = app(DashboardStatsService::class)->forUser();

        $this->assertSame($stats['individual_count'] + $stats['group_members_count'], $stats['total']);
        $this->assertGreaterThan($stats['individual_count'], $stats['total']);
        $this->assertSame(3, $stats['group_members_count']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.summary_individual'), false)
            ->assertSee(__('dashboard.summary_groups'), false)
            ->assertDontSee(__('common.actions'), false);

        $this->get(route('dashboard', ['type' => 'individual']))
            ->assertOk()
            ->assertSee(__('dashboard.recent_filter_individual'), false)
            ->assertSee('WL000001', false)
            ->assertDontSee('WL000002', false)
            ->assertDontSee(__('common.actions'), false)
            ->assertSee(loan_status_label('pending'), false);

        $this->get(route('dashboard', ['type' => 'group']))
            ->assertOk()
            ->assertSee(__('dashboard.recent_filter_group'), false)
            ->assertSee('WL000002', false)
            ->assertDontSee('WL000001', false)
            ->assertDontSee(__('common.actions'), false)
            ->assertSee(loan_status_label('received'), false);
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
            ->get(route('loans.track', ['track_id' => 'WL000005']))
            ->assertOk()
            ->assertSee('WL000005');
    }

    public function test_track_shows_search_form_without_track_id(): void
    {
        $this->actingAsRole('applicant9@wdf.go.tz')
            ->get(route('loans.track'))
            ->assertOk()
            ->assertSee(__('loans.track_search_button'), false);
    }

    public function test_track_draft_by_track_id(): void
    {
        $user = \App\Models\User::where('email', 'applicant9@wdf.go.tz')->firstOrFail();

        DraftLoan::updateOrCreate(
            ['user_id' => $user->id, 'track_id' => 'WL000019'],
            ['form_data' => ['step' => 3, 'requested_amount' => 750000]],
        );

        $this->actingAs($user)
            ->get(route('loans.track', ['track_id' => 'WL000019']))
            ->assertOk()
            ->assertSee('WL000019')
            ->assertSee(__('loans.draft_status'), false)
            ->assertSee(__('loans.resume'), false);
    }

    public function test_applicant_cannot_track_another_users_draft(): void
    {
        $owner = \App\Models\User::where('email', 'applicant9@wdf.go.tz')->firstOrFail();
        $other = \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        DraftLoan::updateOrCreate(
            ['user_id' => $owner->id, 'track_id' => 'WL000019'],
            ['form_data' => ['step' => 2]],
        );

        $this->actingAs($other)
            ->get(route('loans.track', ['track_id' => 'WL000019']))
            ->assertForbidden();
    }

    public function test_staff_cannot_track_another_users_draft(): void
    {
        $owner = \App\Models\User::where('email', 'applicant9@wdf.go.tz')->firstOrFail();

        DraftLoan::updateOrCreate(
            ['user_id' => $owner->id, 'track_id' => 'WL000019'],
            ['form_data' => ['step' => 2, 'requested_amount' => 750000]],
        );

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('loans.track', ['track_id' => 'WL000019']))
            ->assertForbidden();
    }

    public function test_track_not_found_for_invalid_id(): void
    {
        $this->actingAsRole('applicant9@wdf.go.tz')
            ->from(route('loans.track'))
            ->get(route('loans.track', ['track_id' => 'WL999999']))
            ->assertRedirect(route('loans.track'))
            ->assertSessionHasErrors('track_id');
    }
}
