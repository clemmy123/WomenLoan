<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\BusinessDetails;
use App\Models\Council;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\LoanGroupMember;
use App\Models\Region;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\Street;
use App\Models\Ward;
use App\Services\GeneralReportService;
use App\Support\GroupLeadershipRole;
use App\Support\IdentityNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_registered_women_hides_data_until_filters_applied(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.general.women.index'))
            ->assertOk()
            ->assertSee(__('general_reports.apply_filters_prompt'), false)
            ->assertDontSee(__('general_reports.detail_table'), false)
            ->assertSee(__('nav.registered_women'), false)
            ->assertSee(__('reports.period_monthly'), false)
            ->assertSee(__('reports.period_quarterly'), false)
            ->assertSee(__('reports.period_annually'), false)
            ->assertDontSee(__('reports.period_daily'), false);
    }

    public function test_period_query_shows_report_without_daily_option(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.general.women.index', [
                'period' => 'quarterly',
            ]))
            ->assertOk()
            ->assertSee(__('general_reports.detail_table'), false)
            ->assertSee(__('reports.period_monthly'), false)
            ->assertSee(__('reports.period_quarterly'), false)
            ->assertSee(__('reports.period_annually'), false)
            ->assertDontSee(__('reports.period_daily'), false)
            ->assertDontSee(__('reports.period_weekly'), false);
    }

    public function test_ministry_can_view_all_with_names_and_status(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.general.women.index', [
                'applied' => 1,
            ]));

        $response->assertOk();
        $response->assertSee(__('general_reports.title'), false);
        $response->assertSee(__('general_reports.col_name'), false);
        $response->assertSee(__('general_reports.col_status'), false);
        $response->assertSee(__('loans.types.individual'), false);
        $response->assertSee(__('loans.types.group'), false);
        $response->assertSee('generalReportPieChart', false);
        $response->assertSee('generalReportBarChart', false);
        $response->assertSee(__('general_reports.bucket_applied'), false);
        $response->assertSee(__('general_reports.bucket_received'), false);
        $response->assertSee(__('general_reports.bucket_processing'), false);
        $response->assertSee(__('general_reports.bucket_missed'), false);
        $response->assertSee(__('general_reports.women_count'), false);
        $response->assertSee(__('general_reports.individual_count'), false);
        $response->assertSee(__('general_reports.group_count'), false);
        $response->assertSee(__('reports.period_monthly'), false);
        $response->assertSee(__('reports.period_quarterly'), false);
        $response->assertSee(__('reports.period_annually'), false);
        $response->assertDontSee(__('reports.period_daily'), false);
        $response->assertDontSee('name="region_id"', false);
        $response->assertDontSee('name="ward_id"', false);
        $response->assertDontSee('wanaolingana na vichujio', false);
    }

    public function test_individual_filter_lists_names_and_account_numbers(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_type', 'individual')
            ->firstOrFail();

        $loan->update(['bank_number' => '0123456789']);

        $filters = app(GeneralReportService::class)->normalizeFilters([
            'loan_type' => 'individual',
        ]);

        $rows = app(GeneralReportService::class)->allRows($filters);

        $this->assertTrue($rows->isNotEmpty());
        $this->assertTrue($rows->every(fn (array $row) => $row['view'] === 'individual'));
        $this->assertTrue($rows->contains(
            fn (array $row) => $row['name'] === $loan->applicant->full_name
                && $row['account_number'] === '0123456789'
        ));

        $this->get(route('reports.general.women.index', ['loan_type' => 'individual']))
            ->assertOk()
            ->assertSee(__('general_reports.col_account'), false)
            ->assertSee('0123456789', false)
            ->assertDontSee(__('general_reports.col_status'), false)
            ->assertSee(__('general_reports.women_count'), false)
            ->assertDontSee(__('general_reports.group_count'), false)
            ->assertDontSee(__('general_reports.group_members_count'), false)
            ->assertDontSee(__('general_reports.individual_count'), false);
    }

    public function test_group_filter_lists_group_name_and_members(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_type', 'group')
            ->firstOrFail();

        $group = $loan->group()->first() ?? LoanGroup::firstOrCreate(
            ['name' => 'General Report Test Group'],
            [
                'registration_number' => 'WDF-GEN-001',
                'phone' => '255712399101',
            ]
        );

        $loan->update([
            'loan_type' => 'group',
            'loan_group_id' => $group->id,
        ]);

        LoanGroupMember::query()->where('loan_group_id', $group->id)->delete();

        LoanGroupMember::create([
            'loan_group_id' => $group->id,
            'first_name' => 'Amina',
            'last_name' => 'Juma',
            'full_name' => 'Amina Juma',
            'nin' => '19900101123450999101',
            'phone' => '255700000101',
            'is_group_leader' => true,
            'leadership_role' => GroupLeadershipRole::CHAIRPERSON,
        ]);

        LoanGroupMember::create([
            'loan_group_id' => $group->id,
            'first_name' => 'Rehema',
            'last_name' => 'Ally',
            'full_name' => 'Rehema Ally',
            'nin' => '19900101123450999102',
            'phone' => '255700000102',
            'is_group_leader' => false,
            'leadership_role' => GroupLeadershipRole::MEMBER,
        ]);

        $filters = app(GeneralReportService::class)->normalizeFilters([
            'loan_type' => 'group',
        ]);

        $rows = app(GeneralReportService::class)->allRows($filters);
        $match = $rows->firstWhere('group_name', $group->name);

        $this->assertNotNull($match);
        $this->assertContains('Amina Juma', $match['members']);
        $this->assertContains('Rehema Ally', $match['members']);

        $summary = app(GeneralReportService::class)->summary($filters);
        $this->assertSame($summary['group_members_count'], $summary['women_count']);
        $this->assertSame($summary['women_count'], $summary['applied']);

        $this->get(route('reports.general.women.index', ['loan_type' => 'group']))
            ->assertOk()
            ->assertSee($group->name, false)
            ->assertSee('Amina Juma', false)
            ->assertSee('Rehema Ally', false)
            ->assertSee(__('general_reports.col_group'), false)
            ->assertSee(__('general_reports.col_members'), false)
            ->assertSee(__('general_reports.group_count'), false)
            ->assertSee(__('general_reports.group_members_count'), false)
            ->assertDontSee(__('general_reports.individual_count'), false);
    }

    public function test_all_filter_summary_counts_women(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(GeneralReportService::class)->normalizeFilters([
            'loan_type' => '',
        ]);

        $summary = app(GeneralReportService::class)->summary($filters);

        $this->assertSame(
            $summary['individual_count'] + $summary['group_members_count'],
            $summary['women_count']
        );
        $this->assertGreaterThan(0, $summary['women_count']);
        $this->assertSame($summary['women_count'], $summary['applied']);
        $this->assertSame(
            $summary['received'] + $summary['processing'] + $summary['missed'],
            $summary['women_count']
        );
        $this->assertLessThanOrEqual($summary['women_count'], $summary['received']);
        $this->assertLessThanOrEqual($summary['women_count'], $summary['processing']);
        $this->assertLessThanOrEqual($summary['women_count'], $summary['missed']);
    }

    public function test_charts_follow_loan_type_filter(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $all = app(GeneralReportService::class)->chartPayload(
            app(GeneralReportService::class)->normalizeFilters(['loan_type' => ''])
        );
        $individual = app(GeneralReportService::class)->chartPayload(
            app(GeneralReportService::class)->normalizeFilters(['loan_type' => 'individual'])
        );

        $this->assertCount(3, $all['outcome_pie']['labels']);
        $this->assertCount(4, $all['outcome_bars']['labels']);
        $this->assertSame(
            array_sum($all['outcome_pie']['data']),
            $all['outcome_bars']['data'][0]
        );
        $this->assertLessThanOrEqual($all['outcome_bars']['data'][0], $individual['outcome_bars']['data'][0]);
    }

    public function test_ministry_can_export_registered_women_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('reports.general.women.export.excel', [
            'applied' => 1,
        ]))->assertOk();

        $this->get(route('reports.general.women.export.pdf', [
            'loan_type' => 'individual',
        ]))->assertOk();
    }

    public function test_geo_staff_see_only_their_zone_without_geo_filters(): void
    {
        $ownWard = Ward::where('name', 'Hazina Ward')->firstOrFail();
        $ownWard->loadMissing('council.district.region');
        $otherWard = Ward::create([
            'name' => 'Makole General Report',
            'code' => 'MKLGR',
            'council_id' => $ownWard->council_id,
            'district_id' => $ownWard->district_id ?: $ownWard->council->district_id,
        ]);
        $otherRegion = Region::query()->whereKeyNot($ownWard->council->district->region_id)->firstOrFail();
        $otherCouncil = Council::query()
            ->whereHas('district', fn ($query) => $query->where('region_id', $otherRegion->id))
            ->firstOrFail();
        $otherRegionWard = Ward::query()->where('council_id', $otherCouncil->id)->first()
            ?? Ward::create([
                'name' => 'Other Region Ward GR',
                'code' => 'ORWGR',
                'council_id' => $otherCouncil->id,
                'district_id' => $otherCouncil->district_id,
            ]);

        $otherWardLoan = $this->createIndividualLoanInWard($otherWard, 'WLGR9001', '0111999001');
        $otherRegionLoan = $this->createIndividualLoanInWard($otherRegionWard, 'WLGR9002', '0111999002');

        $this->actingAsRole('ward.cdo@wdf.go.tz');
        $wardRows = app(GeneralReportService::class)->allRows(
            app(GeneralReportService::class)->normalizeFilters(['loan_type' => 'individual'])
        );
        $this->assertFalse($wardRows->contains(fn (array $row) => $row['account_number'] === '0111999001'));
        $this->assertFalse($wardRows->contains(fn (array $row) => $row['account_number'] === '0111999002'));

        $this->actingAsRole('council.cdo@wdf.go.tz');
        $councilRows = app(GeneralReportService::class)->allRows(
            app(GeneralReportService::class)->normalizeFilters(['loan_type' => 'individual'])
        );
        $this->assertTrue($councilRows->contains(fn (array $row) => $row['account_number'] === '0111999001'));
        $this->assertFalse($councilRows->contains(fn (array $row) => $row['account_number'] === '0111999002'));

        $this->actingAsRole('region.cdo@wdf.go.tz');
        $regionRows = app(GeneralReportService::class)->allRows(
            app(GeneralReportService::class)->normalizeFilters(['loan_type' => 'individual'])
        );
        $this->assertTrue($regionRows->contains(fn (array $row) => $row['account_number'] === '0111999001'));
        $this->assertFalse($regionRows->contains(fn (array $row) => $row['account_number'] === '0111999002'));

        $this->actingAsRole('ministry@wdf.go.tz');
        $ministryRows = app(GeneralReportService::class)->allRows(
            app(GeneralReportService::class)->normalizeFilters(['loan_type' => 'individual'])
        );
        $this->assertTrue($ministryRows->contains(fn (array $row) => $row['account_number'] === $otherWardLoan->bank_number));
        $this->assertTrue($ministryRows->contains(fn (array $row) => $row['account_number'] === $otherRegionLoan->bank_number));

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('reports.general.women.index', ['applied' => 1]))
            ->assertOk()
            ->assertDontSee('name="region_id"', false)
            ->assertDontSee('name="ward_id"', false);
    }

    public function test_applicant_cannot_open_registered_women_report(): void
    {
        $this->actingAsRole('test@example.com')
            ->get(route('reports.general.women.index'))
            ->assertForbidden();
    }

    private function createIndividualLoanInWard(Ward $ward, string $trackId, string $accountNumber): Loan
    {
        $street = Street::firstOrCreate(
            ['ward_id' => $ward->id, 'name' => 'GR Street '.$ward->id],
            ['code' => 'GRS'.$ward->id],
        );

        $applicant = Applicant::withoutGlobalScopes()->firstOrFail();
        $ward->loadMissing('council.district');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)->create([
            'loan_track_id' => $trackId,
            'user_id' => $applicant->user_id,
            'applicant_id' => $applicant->id,
            'loan_type' => 'individual',
            'requested_amount' => 1500000,
            'proposed_amount' => 0,
            'disbursed_amount' => 0,
            'status' => 'pending',
            'current_step' => 1,
            'applicant_acceptance' => 'pending',
            'bank_name' => 'CRDB Bank',
            'bank_number' => $accountNumber,
        ]);

        BusinessDetails::create([
            'loan_id' => $loan->id,
            'region_id' => $ward->council->district->region_id,
            'district_id' => $ward->district_id ?: $ward->council->district_id,
            'council_id' => $ward->council_id,
            'ward_id' => $ward->id,
            'street_id' => $street->id,
            'business_name' => 'GR Shop',
            'business_phone' => $applicant->phone,
            'business_email' => $applicant->email,
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => IdentityNormalizer::formatTin($trackId === 'WLGR9001' ? '555111001' : '555111002'),
            'business_proposal_document' => 'proposals/demo-proposal.pdf',
            'business_registration_attachment' => 'registrations/demo-registration.pdf',
            'proof_address_attachment' => 'proof-of-address/demo-proof.pdf',
            'application_letter' => 'application-letters/demo-letter.pdf',
            'bank_statement' => 'bank-statements/demo-statement.pdf',
        ]);

        return $loan->fresh();
    }
}
