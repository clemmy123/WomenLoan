<?php

namespace Tests\Feature;

use App\Services\ByTypeReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ByTypeReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_by_type_hides_data_until_filters_applied(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-type.index'))
            ->assertOk()
            ->assertSee(__('by_type_reports.apply_filters_prompt'), false)
            ->assertDontSee(__('by_type_reports.detail_table'), false);
    }

    public function test_ministry_can_view_by_type_with_filters(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-type.index', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
                'loan_type' => 'individual',
            ]));

        $response->assertOk();
        $response->assertSee(__('by_type_reports.title'), false);
        $response->assertSee(__('by_type_reports.all_types'), false);
        $response->assertSee(__('loans.types.individual'), false);
        $response->assertSee(__('loans.types.group'), false);
        $response->assertSee(__('by_type_reports.detail_table'), false);
        $response->assertSee(__('by_type_reports.col_name'), false);
        $response->assertSee(__('by_type_reports.col_phone'), false);
    }

    public function test_by_type_filter_limits_to_selected_type(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ByTypeReportService::class)->normalizeFilters([
            'fiscal_year' => 'all',
            'period' => 'annually',
            'loan_type' => 'individual',
        ]);

        $rows = app(ByTypeReportService::class)->allRows($filters);

        $this->assertTrue($rows->every(fn (array $row) => $row['loan_type'] === 'individual'));
    }

    public function test_group_filter_summary_counts_group_members(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ByTypeReportService::class)->normalizeFilters([
            'fiscal_year' => 'all',
            'period' => 'annually',
            'loan_type' => 'group',
        ]);

        $summary = app(ByTypeReportService::class)->summary($filters);

        $this->assertSame(0, $summary['individual_count']);
        $this->assertArrayHasKey('group_members_count', $summary);
        $this->assertGreaterThanOrEqual(0, $summary['group_members_count']);
    }

    public function test_ministry_can_export_by_type_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('reports.by-type.export.excel', [
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]))->assertOk();

        $this->get(route('reports.by-type.export.pdf', [
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]))->assertOk();
    }
}
