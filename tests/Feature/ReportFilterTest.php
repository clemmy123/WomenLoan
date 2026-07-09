<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_ministry_can_view_reports_with_filters(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.index', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee(__('reports.filters'), false);
        $response->assertSee(__('reports.fiscal_year'), false);
        $response->assertSeeText(__('reports.detail_table'));
        $response->assertSee(__('reports.financial_trend'), false);
        $response->assertSee(__('reports.marital_status'), false);
        $response->assertDontSee('name="is_widowed"', false);
        $response->assertSee('WL000011');
    }

    public function test_ministry_can_view_analytical_reports_menu_page(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.analytical.index'))
            ->assertRedirect(route('reports.analytical.overview'));
    }

    public function test_reports_table_shows_disbursement_columns(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.index', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee(__('reports.disbursed'), false);
        $response->assertSee(__('reports.paid'), false);
        $response->assertSee(__('reports.outstanding'), false);
    }

    public function test_reports_can_filter_by_marital_status(): void
    {
        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000011')
            ->firstOrFail();

        Applicant::withoutGlobalScopes()
            ->whereKey($loan->applicant_id)
            ->update(['marital_status' => 'Widowed']);

        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ReportService::class)->normalizeFilters([
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
            'marital_status' => 'Widowed',
        ]);

        $summary = app(ReportService::class)->summary($filters);

        $this->assertSame(1, $summary['count']);
        $this->assertSame(2800000.0, $summary['total_disbursed']);
    }

    public function test_reports_can_filter_by_actual_loan_type(): void
    {
        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000011')
            ->firstOrFail();

        $loan->update(['loan_type' => 'individual']);

        $this->actingAsRole('ministry@wdf.go.tz');

        $individual = app(ReportService::class)->normalizeFilters([
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
            'loan_type' => 'individual',
        ]);

        $group = app(ReportService::class)->normalizeFilters([
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
            'loan_type' => 'group',
        ]);

        $this->assertSame(1, app(ReportService::class)->summary($individual)['count']);
        $this->assertSame(0, app(ReportService::class)->summary($group)['count']);

        $charts = app(ReportService::class)->chartData($individual);
        $this->assertSame([loan_type_label('individual'), loan_type_label('group')], $charts['loan_type']['labels']);
        $this->assertSame([1, 0], $charts['loan_type']['data']);
        $this->assertCount(count(Applicant::MARITAL_STATUSES), $charts['marital_status']['labels']);
    }

    public function test_reports_age_filter_uses_birthday_aware_age(): void
    {
        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000011')
            ->firstOrFail();

        // Today is 2026-07-09 in this project context; birthday tomorrow => still previous age.
        Applicant::withoutGlobalScopes()
            ->whereKey($loan->applicant_id)
            ->update(['dob' => now()->subYears(30)->addDay()->toDateString()]);

        $this->actingAsRole('ministry@wdf.go.tz');

        $age29 = app(ReportService::class)->normalizeFilters([
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
            'age_min' => 29,
            'age_max' => 29,
        ]);

        $age30 = app(ReportService::class)->normalizeFilters([
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
            'age_min' => 30,
            'age_max' => 30,
        ]);

        $this->assertSame(1, app(ReportService::class)->summary($age29)['count']);
        $this->assertSame(0, app(ReportService::class)->summary($age30)['count']);
    }

    public function test_ministry_can_export_reports_excel(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.export.excel', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_ministry_can_export_reports_pdf(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.export.pdf', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type')
        );
    }
}
