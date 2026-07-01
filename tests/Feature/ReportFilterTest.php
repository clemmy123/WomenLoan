<?php

namespace Tests\Feature;

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
                'period' => 'monthly',
            ]));

        $response->assertOk();
        $response->assertSee(__('reports.filters'), false);
        $response->assertSeeText(__('reports.detail_table'));
        $response->assertSee(__('reports.financial_trend'), false);
        $response->assertSee('WL000011');
    }

    public function test_reports_table_shows_disbursement_columns(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.index', ['period' => 'annually']));

        $response->assertOk();
        $response->assertSee(__('reports.disbursed'), false);
        $response->assertSee(__('reports.paid'), false);
        $response->assertSee(__('reports.outstanding'), false);
    }

    public function test_ministry_can_export_reports_excel(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.export.excel', ['period' => 'monthly']));

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
            ->get(route('reports.export.pdf', ['period' => 'monthly']));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type')
        );
    }
}
