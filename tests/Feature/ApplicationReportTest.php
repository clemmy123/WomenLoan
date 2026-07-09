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
        $response->assertSee(__('application_reports.bank_name'), false);
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
        $response->assertSee('WL000011');
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
