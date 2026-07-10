<?php

namespace Tests\Feature;

use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanActionPriorityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_actionable_loans_appear_first_and_show_new_badge_for_ward_cdo(): void
    {
        $actionable = $this->loanByTrack('WL000002');
        $this->assertSame(1, $actionable->current_step);
        $this->assertSame('received', $actionable->status);

        // Older non-actionable loan for this ward CDO (already past step 1).
        Loan::withoutGlobalScopes()->where('loan_track_id', 'WL000001')->update([
            'current_step' => 2,
            'status' => 'in_review',
            'created_at' => now()->addDay(),
            'updated_at' => now()->addDay(),
        ]);

        $response = $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('loan-applications.index'));

        $response->assertOk();
        $response->assertSee(__('loans.action_new'), false);
        $response->assertSee($actionable->loan_track_id, false);

        $html = $response->getContent();
        $actionablePos = strpos($html, $actionable->loan_track_id);
        $otherPos = strpos($html, 'WL000001');

        $this->assertNotFalse($actionablePos);
        $this->assertNotFalse($otherPos);
        $this->assertLessThan($otherPos, $actionablePos, 'Actionable loan should be listed before others');
    }

    public function test_loan_needs_user_action_helper_matches_ward_forward_gate(): void
    {
        $loan = $this->loanByTrack('WL000002');
        $user = \App\Models\User::where('email', 'ward.cdo@wdf.go.tz')->firstOrFail();

        $this->assertTrue(loan_needs_user_action($loan, $user));
        $this->assertTrue(loan_has_workflow_actions($loan, $user));

        $loan->update(['status' => 'pending']);
        $loan->refresh();

        $this->assertFalse(loan_needs_user_action($loan, $user));
    }
}
