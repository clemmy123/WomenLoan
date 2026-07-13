<?php

namespace Tests\Feature;

use App\Services\BySectorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BySectorReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_by_sector_hides_data_until_filters_applied(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-sector.index'))
            ->assertOk()
            ->assertSee(__('by_sector_reports.apply_filters_prompt'), false)
            ->assertDontSee(__('by_sector_reports.detail_table'), false);
    }

    public function test_ministry_can_view_by_sector_with_filters(): void
    {
        $sectorName = app(BySectorReportService::class)->sectors()->first()?->name;

        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-sector.index', [
                'period' => 'annually',
                'business_sector' => $sectorName,
            ]));

        $response->assertOk();
        $response->assertSee(__('by_sector_reports.title'), false);
        $response->assertSee(__('by_sector_reports.all_sectors'), false);
        $response->assertSee(__('by_sector_reports.detail_table'), false);
        $response->assertSee(__('by_sector_reports.col_name'), false);
        $response->assertDontSee('name="fiscal_year"', false);
    }

    public function test_by_sector_filter_limits_to_selected_sector(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $sectorName = app(BySectorReportService::class)->sectors()->first()?->name;
        $this->assertNotNull($sectorName);

        $filters = app(BySectorReportService::class)->normalizeFilters([
            'period' => 'annually',
            'business_sector' => $sectorName,
        ]);

        $this->assertSame($sectorName, $filters['business_sector']);

        $rows = app(BySectorReportService::class)->allRows($filters);

        foreach ($rows as $row) {
            $this->assertSame($sectorName, $row['sector']);
        }
    }

    public function test_ministry_can_export_by_sector_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('reports.by-sector.export.excel', [
            'period' => 'annually',
        ]))->assertOk();

        $this->get(route('reports.by-sector.export.pdf', [
            'period' => 'annually',
        ]))->assertOk();
    }
}
