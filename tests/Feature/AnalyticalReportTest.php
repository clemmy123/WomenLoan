<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Scopes\ApprovalLevelScope;
use App\Services\AnalyticalReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticalReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_analytical_index_redirects_to_overview(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.analytical.index'))
            ->assertRedirect(route('reports.analytical.overview'));
    }

    public function test_ministry_can_view_analytical_overview(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.analytical.overview', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee(__('analytical_reports.overview_title'), false);
        $response->assertSee(__('analytical_reports.fiscal_year'), false);
        $response->assertSee(__('analytical_reports.individual_repayments'), false);
        $response->assertSee(__('analytical_reports.group_repayments'), false);
        $response->assertSee(__('analytical_reports.chart_by_type'), false);
        $response->assertSee(__('analytical_reports.hide_filters'), false);
        $response->assertDontSee('name="quarter"', false);
        $response->assertDontSee('name="sort"', false);
    }

    public function test_payment_report_hides_data_until_filters_applied(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.analytical.overview'));

        $response->assertOk();
        $response->assertSee(__('analytical_reports.apply_filters_prompt'), false);
        $response->assertDontSee(__('analytical_reports.individual_repayments'), false);
        $response->assertDontSee(__('analytical_reports.chart_by_type'), false);
    }

    public function test_default_fiscal_year_is_current_july_to_june(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-09'));

        $service = app(AnalyticalReportService::class);
        $filters = $service->normalizeFilters([]);

        $this->assertSame('2026/2027', $filters['fiscal_year']);
        $this->assertSame('2026-07-01', $filters['date_from']);
        $this->assertSame('2027-06-30', $filters['date_to']);
        $this->assertArrayHasKey('2025/2026', $service->fiscalYearOptions());
        $this->assertArrayHasKey('2018/2019', $service->fiscalYearOptions());
        $this->assertArrayNotHasKey('2027/2028', $service->fiscalYearOptions());

        Carbon::setTestNow();
    }

    public function test_future_fiscal_year_appears_only_after_july_first(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-06-30'));
        $this->assertArrayNotHasKey('2027/2028', app(AnalyticalReportService::class)->fiscalYearOptions());

        Carbon::setTestNow(Carbon::parse('2027-07-01'));
        $this->assertArrayHasKey('2027/2028', app(AnalyticalReportService::class)->fiscalYearOptions());
        $this->assertSame('2027/2028', app(AnalyticalReportService::class)->currentFiscalYearKey());

        Carbon::setTestNow();
    }

    public function test_analytical_summary_uses_loan_disbursed_amount_in_its_fiscal_year(): void
    {
        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000012')
            ->firstOrFail();

        LoanPayment::where('loan_id', $loan->id)->update([
            'amount_disbursed' => 9999999,
        ]);

        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(AnalyticalReportService::class)->normalizeFilters([
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]);

        $summary = app(AnalyticalReportService::class)->summary($filters);

        $this->assertSame(1, $summary['total_count']);
        $this->assertSame(2800000.0, $summary['total_disbursed']);
        $this->assertSame(2800000.0, $summary['individual_disbursed'] + $summary['group_disbursed']);
    }

    public function test_quarter_resolves_inside_selected_fiscal_year(): void
    {
        $filters = app(AnalyticalReportService::class)->normalizeFilters([
            'fiscal_year' => '2026/2027',
            'period' => 'monthly',
            'quarter' => 'q1',
            'use_custom_dates' => '1',
            'date_from' => '2020-01-01',
            'date_to' => '2020-01-31',
        ]);

        $this->assertSame('2026/2027', $filters['fiscal_year']);
        $this->assertSame('q1', $filters['quarter']);
        $this->assertSame('2027-01-01', $filters['date_from']);
        $this->assertSame('2027-03-31', $filters['date_to']);
        $this->assertNull($filters['use_custom_dates']);
    }

    public function test_ministry_can_export_analytical_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $excel = $this->get(route('reports.analytical.export.excel', [
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]));
        $excel->assertOk();
        $excel->assertDownload();

        $pdf = $this->get(route('reports.analytical.export.pdf', [
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]));
        $pdf->assertOk();
        $pdf->assertDownload();
    }
}
