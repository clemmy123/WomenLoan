<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use App\Services\ByRegionReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ByRegionReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_by_region_hides_data_until_filters_applied(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-region.index'))
            ->assertOk()
            ->assertSee(__('by_region_reports.apply_filters_prompt'), false)
            ->assertDontSee(__('by_region_reports.detail_table'), false);
    }

    public function test_ministry_can_view_by_region_with_filters(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-region.index', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee(__('by_region_reports.title'), false);
        $response->assertSee(__('by_region_reports.all_regions'), false);
        $response->assertSee(__('by_region_reports.detail_table'), false);
        $response->assertSee(__('by_region_reports.col_name'), false);
        $response->assertSee(__('by_region_reports.col_phone'), false);
        $response->assertSee(__('reports.period_daily'), false);
        $response->assertSee(__('reports.period_quarterly'), false);
    }

    public function test_ministry_can_export_by_region_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('reports.by-region.export.excel', [
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]))->assertOk();

        $this->get(route('reports.by-region.export.pdf', [
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]))->assertOk();
    }

    public function test_ward_cdo_by_region_is_scoped_to_own_zone(): void
    {
        $ward = Ward::where('name', 'Tambukareli')->firstOrFail();
        $ward->loadMissing('council.district.region');
        $ownRegion = $ward->council->district->region;
        $otherRegion = Region::query()->whereKeyNot($ownRegion->id)->first();

        $response = $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('reports.by-region.index', [
                'fiscal_year' => 'all',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee($ownRegion->name, false);

        if ($otherRegion) {
            $response->assertDontSee('>'.$otherRegion->name.'<', false);
        }

        $this->actingAsRole('ward.cdo@wdf.go.tz');

        $filters = app(ByRegionReportService::class)->normalizeFilters([
            'fiscal_year' => 'all',
            'period' => 'annually',
            'region_id' => $otherRegion?->id,
        ]);

        $this->assertSame((string) $ownRegion->id, (string) $filters['region_id']);
    }

    public function test_ministry_sees_all_regions_option_and_region_list(): void
    {
        $regionCount = Region::query()->count();

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-region.index'))
            ->assertOk()
            ->assertSee(__('by_region_reports.all_regions'), false);

        $regions = app(ByRegionReportService::class)->regions();
        $this->assertCount($regionCount, $regions);
        $this->assertGreaterThan(1, $regionCount);
    }

    public function test_by_region_accepts_cascading_geo_filters(): void
    {
        $ward = Ward::where('name', 'Tambukareli')->firstOrFail();
        $ward->loadMissing('council.district.region');

        $filters = app(ByRegionReportService::class)->normalizeFilters([
            'fiscal_year' => 'all',
            'period' => 'annually',
            'region_id' => $ward->council->district->region_id,
            'district_id' => $ward->council->district_id,
            'council_id' => $ward->council_id,
            'ward_id' => $ward->id,
        ]);

        $this->assertSame((string) $ward->council->district->region_id, (string) $filters['region_id']);
        $this->assertSame((string) $ward->council->district_id, (string) $filters['district_id']);
        $this->assertSame((string) $ward->council_id, (string) $filters['council_id']);
        $this->assertSame((string) $ward->id, (string) $filters['ward_id']);
    }

    public function test_by_region_clears_child_geo_when_region_is_all(): void
    {
        $filters = app(ByRegionReportService::class)->normalizeFilters([
            'fiscal_year' => 'all',
            'period' => 'annually',
            'region_id' => '',
            'district_id' => '99',
            'council_id' => '88',
            'ward_id' => '77',
        ]);

        $this->assertEmpty($filters['region_id']);
        $this->assertEmpty($filters['district_id']);
        $this->assertEmpty($filters['council_id']);
        $this->assertEmpty($filters['ward_id']);
    }
}
