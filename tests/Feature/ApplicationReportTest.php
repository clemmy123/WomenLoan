<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_ministry_can_view_application_reports_with_filters(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.applications.index', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee(__('application_reports.filters'), false);
        $response->assertSee(__('reports.fiscal_year'), false);
        $response->assertSee(__('application_reports.all_statuses'), false);
        $response->assertSee(__('application_reports.detail_table'), false);
    }

    public function test_application_reports_table_shows_required_columns(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.applications.index', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee(__('application_reports.track_id'), false);
        $response->assertSee(__('application_reports.full_name'), false);
        $response->assertSee(__('application_reports.amount_requested'), false);
        $response->assertSee(__('application_reports.amount_disbursed'), false);
        $response->assertDontSee(__('application_reports.bank_name'), false);
        $response->assertSee(__('application_reports.outstanding'), false);
        $response->assertSee(__('application_reports.amount_repaid'), false);
    }

    public function test_application_reports_can_filter_by_status(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.applications.index', [
                'fiscal_year' => '2026/2027',
                'status' => 'disbursed',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee('WL000012');
    }

    public function test_group_application_lists_member_names(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $loan = \App\Models\Loan::withoutGlobalScope(\App\Models\Scopes\ApprovalLevelScope::class)
            ->where('loan_type', 'group')
            ->first();

        $this->assertNotNull($loan);

        $filters = app(\App\Services\ApplicationReportService::class)->normalizeFilters([
            'fiscal_year' => 'all',
            'period' => 'annually',
        ]);

        $row = app(\App\Services\ApplicationReportService::class)
            ->allRows($filters)
            ->firstWhere('track_id', $loan->loan_track_id);

        $this->assertNotNull($row);
        $this->assertSame('group', $row['loan_type']);
        $this->assertNotEmpty($row['members']);
        $this->assertSame($loan->group?->name, $row['full_name']);
    }

    public function test_application_reports_show_export_buttons(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.applications.index', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee(__('application_reports.export_excel'), false);
        $response->assertSee(__('application_reports.export_pdf'), false);
    }

    public function test_ministry_can_export_application_reports_excel(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.applications.export.excel', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            'wdf-application-reports-',
            $response->headers->get('content-disposition') ?? ''
        );
    }

    public function test_ministry_can_export_application_reports_pdf(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.applications.export.pdf', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            'wdf-application-reports-',
            $response->headers->get('content-disposition') ?? ''
        );
    }
}
