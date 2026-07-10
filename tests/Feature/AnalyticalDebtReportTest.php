<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Scopes\ApprovalLevelScope;
use App\Services\AnalyticalDebtReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticalDebtReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_ministry_can_view_outstanding_and_overdue_reports(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.analytical.outstanding'))
            ->assertOk()
            ->assertSee(__('analytical_reports.outstanding_title'), false)
            ->assertSee(__('analytical_reports.col_name'), false)
            ->assertSee(__('analytical_reports.col_disbursed'), false)
            ->assertSee(__('analytical_reports.col_outstanding'), false)
            ->assertSee(__('analytical_reports.col_elapsed'), false);

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('reports.analytical.overdue'))
            ->assertOk()
            ->assertSee(__('analytical_reports.overdue_title'), false);
    }

    public function test_overdue_mode_includes_missed_installment_balances(): void
    {
        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('status', 'disbursed')
            ->where('disbursed_amount', '>', 0)
            ->first();

        if (! $loan) {
            $this->markTestSkipped('No disbursed loan in seed data');
        }

        $payment = LoanPayment::withoutGlobalScopes()
            ->where('loan_id', $loan->id)
            ->orderBy('id')
            ->first();

        if (! $payment) {
            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'amount_requested' => $loan->requested_amount,
                'amount_disbursed' => $loan->disbursed_amount,
                'interest_amount' => round((float) $loan->disbursed_amount * 0.16, 2),
                'amount_paid' => 0,
                'outstanding_debt' => round((float) $loan->disbursed_amount * 1.16, 2),
                'grace_period_days' => 90,
                'start_date' => now()->subYear()->toDateString(),
                'end_date' => now()->subMonth()->toDateString(),
                'payment_interval' => 'monthly',
                'payment_history' => [
                    'installments' => [
                        [
                            'installment' => 1,
                            'due_date' => now()->subMonths(2)->toDateString(),
                            'amount_due' => 100000,
                            'amount_paid' => 0,
                            'status' => 'pending',
                        ],
                    ],
                    'transactions' => [],
                ],
            ]);
        } else {
            $payment->update([
                'outstanding_debt' => max(1, (float) $payment->outstanding_debt),
                'end_date' => now()->subMonth()->toDateString(),
                'payment_history' => [
                    'installments' => [
                        [
                            'installment' => 1,
                            'due_date' => now()->subMonths(2)->toDateString(),
                            'amount_due' => 100000,
                            'amount_paid' => 0,
                            'status' => 'pending',
                        ],
                    ],
                    'transactions' => $payment->payment_history['transactions'] ?? [],
                ],
            ]);
        }

        $this->actingAsRole('ministry@wdf.go.tz');

        $service = app(AnalyticalDebtReportService::class);
        $filters = $service->normalizeFilters([
            'fiscal_year' => 'all',
            'period' => 'annually',
        ]);

        $rows = $service->allRows($filters, AnalyticalDebtReportService::MODE_OVERDUE);

        $this->assertTrue(
            $rows->contains(fn ($row) => $row['track_id'] === $loan->loan_track_id),
            'Overdue report should include the loan with a missed installment'
        );
    }
}
