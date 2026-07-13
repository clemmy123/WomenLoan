<?php

namespace Tests\Feature;

use App\Services\ByBankReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ByBankReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_by_bank_hides_data_until_filters_applied(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-bank.index'))
            ->assertOk()
            ->assertSee(__('by_bank_reports.apply_filters_prompt'), false)
            ->assertDontSee(__('by_bank_reports.detail_table'), false);
    }

    public function test_ministry_can_view_by_bank_with_filters(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.by-bank.index', [
                'fiscal_year' => '2025/2026',
            ]));

        $response->assertOk();
        $response->assertSee(__('by_bank_reports.title'), false);
        $response->assertSee(__('by_bank_reports.all_banks'), false);
        $response->assertSee(__('by_bank_reports.fiscal_year'), false);
        $response->assertSee(__('by_bank_reports.detail_table'), false);
        $response->assertDontSee('name="period"', false);
    }

    public function test_by_bank_filter_limits_to_selected_bank(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $bank = app(ByBankReportService::class)->banks()[0] ?? null;
        $this->assertNotNull($bank);

        $filters = app(ByBankReportService::class)->normalizeFilters([
            'fiscal_year' => 'all',
            'bank_name' => $bank,
        ]);

        $this->assertSame($bank, $filters['bank_name']);

        $rows = app(ByBankReportService::class)->allRows($filters);

        foreach ($rows as $row) {
            $this->assertSame($bank, $row['bank']);
        }
    }

    public function test_ministry_can_export_by_bank_excel_and_pdf(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->get(route('reports.by-bank.export.excel', [
            'fiscal_year' => '2025/2026',
        ]))->assertOk();

        $this->get(route('reports.by-bank.export.pdf', [
            'fiscal_year' => '2025/2026',
        ]))->assertOk();
    }
}
