<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Scopes\ApprovalLevelScope;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTotalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_report_total_disbursed_matches_sum_of_loan_disbursed_amounts(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ReportService::class)->normalizeFilters([
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]);

        $summary = app(ReportService::class)->summary($filters);

        $expected = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('status', 'disbursed')
            ->where('disbursed_amount', '>', 0)
            ->get()
            ->sum(fn (Loan $loan) => (float) $loan->disbursed_amount);

        $this->assertSame(2800000.0, $expected);
        $this->assertSame($expected, $summary['total_disbursed']);
        $this->assertSame(1, $summary['count']);
    }

    public function test_report_uses_loan_disbursed_amount_not_stale_payment_ledger(): void
    {
        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000012')
            ->firstOrFail();

        LoanPayment::where('loan_id', $loan->id)->update([
            'amount_disbursed' => 9999999,
        ]);

        $this->actingAsRole('ministry@wdf.go.tz');

        $filters = app(ReportService::class)->normalizeFilters([
            'fiscal_year' => '2025/2026',
            'period' => 'annually',
        ]);

        $summary = app(ReportService::class)->summary($filters);
        $rows = app(ReportService::class)->allRows($filters);
        $row = $rows->firstWhere('track_id', 'WL000012');

        $this->assertSame(2800000.0, $summary['total_disbursed']);
        $this->assertSame(2800000.0, $row['disbursed']);
    }

    public function test_reports_page_shows_actual_disbursed_total(): void
    {
        $response = $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.index', [
                'fiscal_year' => '2025/2026',
                'period' => 'annually',
            ]));

        $response->assertOk();
        $response->assertSee(format_tzs(2800000), false);
        $response->assertSee(__('reports.fiscal_year'), false);
    }
}
