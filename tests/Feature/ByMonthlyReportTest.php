<?php

namespace Tests\Feature;

use App\Services\ByMonthlyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ByMonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_by_monthly_hides_data_until_filters_applied(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-monthly.index'))
            ->assertOk()
            ->assertSee(__('by_monthly_reports.apply_filters_prompt'), false)
            ->assertDontSee(__('by_monthly_reports.detail_table'), false);
    }

    public function test_ministry_can_view_by_monthly_with_filters(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-monthly.index', [
                'month' => 1,
            ]));

        $response->assertOk();
        $response->assertSee(__('by_monthly_reports.title'), false);
        $response->assertSee(__('by_monthly_reports.all_months'), false);
        $response->assertSee(__('by_monthly_reports.month_1'), false);
        $response->assertSee(__('by_monthly_reports.month_12'), false);
        $response->assertSee(__('by_monthly_reports.detail_table'), false);
        $response->assertDontSee('name="fiscal_year"', false);
    }

    public function test_month_filter_is_normalized(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ByMonthlyReportService::class)->normalizeFilters([
            'month' => '7',
        ]);

        $this->assertSame(7, $filters['month']);
    }

    public function test_ministry_can_export_by_monthly_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('reports.by-monthly.export.excel', [
            'month' => 1,
        ]))->assertOk();

        $this->get(route('reports.by-monthly.export.pdf', [
            'month' => 1,
        ]))->assertOk();
    }
}
