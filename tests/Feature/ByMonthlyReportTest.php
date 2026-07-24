<?php

namespace Tests\Feature;

use App\Services\ByMonthlyReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ByMonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
        Carbon::setTestNow(Carbon::parse('2026-07-24 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
                'month' => 7,
            ]));

        $response->assertOk();
        $response->assertSee(__('by_monthly_reports.title'), false);
        $response->assertSee(__('by_monthly_reports.year'), false);
        $response->assertSee('2026', false);
        $response->assertSee(__('by_monthly_reports.all_months'), false);
        $response->assertSee(__('by_monthly_reports.month_1'), false);
        $response->assertSee(__('by_monthly_reports.month_7'), false);
        $response->assertDontSee(__('by_monthly_reports.month_12'), false);
        $response->assertSee(__('by_monthly_reports.detail_table'), false);
        $response->assertSee(__('by_monthly_reports.loan_count'), false);
        $response->assertDontSee(__('by_monthly_reports.disbursed_list'), false);
        $response->assertDontSee(__('by_monthly_reports.debts_list'), false);
        $response->assertDontSee('name="date_from"', false);
        $response->assertDontSee('name="sort"', false);
    }

    public function test_year_is_locked_to_current_calendar_year(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ByMonthlyReportService::class)->normalizeFilters([
            'month' => '7',
            'year' => '2024',
        ]);

        $this->assertSame(2026, $filters['year']);
        $this->assertSame(7, $filters['month']);
    }

    public function test_future_months_are_rejected_for_current_year(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ByMonthlyReportService::class)->normalizeFilters([
            'month' => '12',
        ]);

        $this->assertNull($filters['month']);
        $this->assertSame(2026, $filters['year']);
    }

    public function test_summary_scopes_to_locked_year(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ByMonthlyReportService::class)->normalizeFilters([
            'month' => '',
        ]);

        $summary = app(ByMonthlyReportService::class)->summary($filters);

        $this->assertSame(2026, $filters['year']);
        $this->assertArrayHasKey('count', $summary);
        $this->assertArrayHasKey('total_disbursed', $summary);
        $this->assertArrayHasKey('total_outstanding', $summary);
        $this->assertArrayNotHasKey('debt_count', $summary);
    }

    public function test_ministry_can_export_by_monthly_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('reports.by-monthly.export.excel', [
            'month' => 7,
        ]))->assertOk();

        $this->get(route('reports.by-monthly.export.pdf', [
            'month' => 7,
        ]))->assertOk();
    }
}
