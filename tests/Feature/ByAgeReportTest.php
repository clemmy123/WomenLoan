<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\LoanGroupMember;
use App\Models\Scopes\ApprovalLevelScope;
use App\Services\ByAgeReportService;
use App\Support\GroupLeadershipRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ByAgeReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_by_age_hides_data_until_filters_applied(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-age.index'))
            ->assertOk()
            ->assertSee(__('by_age_reports.apply_filters_prompt'), false)
            ->assertDontSee(__('by_age_reports.detail_table'), false);
    }

    public function test_ministry_can_view_by_age_with_filters(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-age.index', [
                'age_min' => 18,
                'age_max' => 45,
            ]));

        $response->assertOk();
        $response->assertSee(__('by_age_reports.title'), false);
        $response->assertSee(__('by_age_reports.all_regions'), false);
        $response->assertSee(__('by_age_reports.age_min'), false);
        $response->assertSee(__('by_age_reports.age_max'), false);
        $response->assertSee(__('by_age_reports.detail_table'), false);
        $response->assertDontSee('name="fiscal_year"', false);
        $response->assertSee('name="sort"', false);
        $response->assertSee('name="district_id"', false);
    }

    public function test_age_filters_are_normalized_and_swapped(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ByAgeReportService::class)->normalizeFilters([
            'age_min' => '45',
            'age_max' => '18',
        ]);

        $this->assertSame(18, $filters['age_min']);
        $this->assertSame(45, $filters['age_max']);
    }

    public function test_group_loan_lists_every_member_as_own_row(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('status', 'disbursed')
            ->where('loan_type', 'group')
            ->where('disbursed_amount', '>', 0)
            ->first();

        if (! $loan) {
            $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
                ->where('status', 'disbursed')
                ->where('disbursed_amount', '>', 0)
                ->firstOrFail();

            $group = LoanGroup::firstOrCreate(
                ['name' => 'Age Report Test Group'],
                [
                    'registration_number' => 'WDF-AGE-001',
                    'phone' => '255712399001',
                ]
            );

            $loan->update([
                'loan_type' => 'group',
                'loan_group_id' => $group->id,
            ]);
        }

        $group = $loan->group()->firstOrFail();

        LoanGroupMember::query()->where('loan_group_id', $group->id)->delete();

        LoanGroupMember::create([
            'loan_group_id' => $group->id,
            'first_name' => 'Age',
            'last_name' => 'Young',
            'full_name' => 'Age Member Young',
            'nin' => '19900101123450999001',
            'dob' => now()->subYears(25)->toDateString(),
            'phone' => '255700000001',
            'is_group_leader' => true,
            'leadership_role' => GroupLeadershipRole::CHAIRPERSON,
        ]);

        LoanGroupMember::create([
            'loan_group_id' => $group->id,
            'first_name' => 'Age',
            'last_name' => 'Older',
            'full_name' => 'Age Member Older',
            'nin' => '19900101123450999002',
            'dob' => now()->subYears(40)->toDateString(),
            'phone' => '255700000002',
            'is_group_leader' => false,
            'leadership_role' => GroupLeadershipRole::MEMBER,
        ]);

        $filters = app(ByAgeReportService::class)->normalizeFilters([
            'age_min' => 18,
            'age_max' => 60,
        ]);

        $rows = app(ByAgeReportService::class)->allRows($filters);
        $names = $rows->pluck('name')->all();

        $this->assertContains('Age Member Young', $names);
        $this->assertContains('Age Member Older', $names);

        $youngOnly = app(ByAgeReportService::class)->normalizeFilters([
            'age_min' => 25,
            'age_max' => 25,
        ]);

        $youngRows = app(ByAgeReportService::class)->allRows($youngOnly);
        $youngNames = $youngRows->pluck('name')->all();

        $this->assertContains('Age Member Young', $youngNames);
        $this->assertNotContains('Age Member Older', $youngNames);
    }

    public function test_ministry_can_export_by_age_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('reports.by-age.export.excel', [
            'age_min' => 18,
            'age_max' => 45,
        ]))->assertOk();

        $this->get(route('reports.by-age.export.pdf', [
            'age_min' => 18,
            'age_max' => 45,
        ]))->assertOk();
    }
}
