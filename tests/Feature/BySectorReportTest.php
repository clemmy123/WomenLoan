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
        $response->assertSee('name="fiscal_year"', false);
        $response->assertSee('name="sort"', false);
        $response->assertSee('bySectorAllSectorsChart', false);
    }

    public function test_by_sector_chart_lists_all_sectors(): void
    {
        $service = app(BySectorReportService::class);
        $filters = $service->normalizeFilters([
            'fiscal_year' => 'all',
            'period' => 'annually',
        ]);

        $sectorChart = $service->sectorChartData($filters);
        $sectorNames = $service->sectors()->pluck('name')->all();

        $this->assertGreaterThanOrEqual(count($sectorNames), count($sectorChart['labels']));

        foreach ($sectorNames as $name) {
            $this->assertContains($name, $sectorChart['labels']);
        }

        $this->assertGreaterThan(1, count($sectorNames));
        $this->assertContains('MIRADI MBALIMBALI', $sectorNames);
        $this->assertContains('AFYA BINAFSI, UANGALIZI NA HUDUMA ZA USTAWI', $sectorNames);
        $this->assertNotContains('KILIMO', $sectorNames);
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
