<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChiefAccountantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_chief_sees_approved_assigned_and_disbursed_loans(): void
    {
        $approved = $this->loanByTrack('WL000009');
        $ready = $this->loanByTrack('WL000010');
        $disbursed = $this->loanByTrack('WL000011');
        $pending = $this->loanByTrack('WL000001');

        $this->assertSame('approved', $approved->status);
        $this->assertSame(8, $approved->current_step);

        $response = $this->actingAsRole('chief@wdf.go.tz')
            ->get(route('loan-applications.index'));

        $response->assertOk();
        $response->assertSee(__('nav.assign_accountant_queue'), false);
        $response->assertSee('WL000009', false);
        $response->assertSee('WL000010', false);
        $response->assertSee('WL000011', false);
        $response->assertDontSee('WL000001', false);

        $this->actingAsRole('chief@wdf.go.tz')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.chief_queue_title'), false)
            ->assertSee('WL000009', false)
            ->assertSee('WL000010', false)
            ->assertDontSee('WL000001', false);

        $this->assertTrue(Loan::query()->whereKey($approved->id)->exists());
        $this->assertTrue(Loan::query()->whereKey($ready->id)->exists());
        $this->assertTrue(Loan::query()->whereKey($disbursed->id)->exists());
        $this->assertFalse(Loan::query()->whereKey($pending->id)->exists());
    }

    public function test_chief_still_sees_loan_after_assigning_accountant(): void
    {
        $loan = $this->loanByTrack('WL000009');
        $accountant = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $this->actingAsRole('chief@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'assign_accountant',
                'accountant_id' => $accountant->id,
                'comments' => 'Assigning disbursement officer.',
            ])
            ->assertRedirect(route('loan-applications.show', $loan->hashid));

        $loan->refresh();
        $this->assertSame('ready_for_disbursement', $loan->status);

        $this->actingAsRole('chief@wdf.go.tz')
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertSee('WL000009', false)
            ->assertSee(loan_status_label('ready_for_disbursement'), false);

        $this->actingAsRole('chief@wdf.go.tz')
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk();
    }

    public function test_accountant_sees_only_assigned_ready_or_disbursed_loans(): void
    {
        $ready = $this->loanByTrack('WL000010');
        $disbursed = $this->loanByTrack('WL000011');
        $approved = $this->loanByTrack('WL000009');
        $pending = $this->loanByTrack('WL000001');

        $accountant = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();
        $otherAccountant = User::where('email', 'accountant2@wdf.go.tz')->firstOrFail();

        $this->assertSame($accountant->id, $ready->officer_id);
        $this->assertSame($accountant->id, $disbursed->officer_id);

        $response = $this->actingAs($accountant)
            ->get(route('loan-applications.index'));

        $response->assertOk();
        $response->assertSee(__('nav.my_disbursements'), false);
        $response->assertSee('WL000010', false);
        $response->assertSee('WL000011', false);
        $response->assertDontSee('WL000009', false);
        $response->assertDontSee('WL000001', false);

        $this->actingAs($accountant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.accountant_queue_title'), false)
            ->assertSee('WL000010', false)
            ->assertDontSee('WL000001', false);

        $this->actingAs($otherAccountant)
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertDontSee('WL000010', false)
            ->assertDontSee('WL000011', false);

        $this->actingAs($accountant);
        $this->assertTrue(Loan::query()->whereKey($ready->id)->exists());
        $this->assertTrue(Loan::query()->whereKey($disbursed->id)->exists());
        $this->assertFalse(Loan::query()->whereKey($approved->id)->exists());
        $this->assertFalse(Loan::query()->whereKey($pending->id)->exists());
    }

    public function test_chief_cannot_open_loan_outside_approved_queue(): void
    {
        $pending = $this->loanByTrack('WL000001');

        $this->actingAsRole('chief@wdf.go.tz')
            ->get(route('loan-applications.show', $pending->hashid))
            ->assertNotFound();
    }

    public function test_accountant_cannot_open_unassigned_loan(): void
    {
        $approved = $this->loanByTrack('WL000009');

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->get(route('loan-applications.show', $approved->hashid))
            ->assertNotFound();
    }

    public function test_accountant_still_sees_loan_after_disbursing_money(): void
    {
        $loan = $this->loanByTrack('WL000010');
        $accountant = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $this->actingAs($accountant)
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'disburse',
                'grace_period_months' => 3,
                'comments' => 'Funds disbursed.',
            ])
            ->assertRedirect(route('loan-applications.show', $loan->hashid));

        $loan->refresh();
        $this->assertSame('disbursed', $loan->status);
        $this->assertSame($accountant->id, $loan->officer_id);

        $this->actingAs($accountant)
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertSee('WL000010', false)
            ->assertSee(loan_status_label('disbursed'), false);

        $this->actingAs($accountant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('WL000010', false);

        $this->actingAs($accountant)
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk();
    }

    public function test_scope_still_allows_unscoped_admin_access(): void
    {
        $count = Loan::withoutGlobalScope(ApprovalLevelScope::class)->count();

        $this->assertGreaterThan(0, $count);
    }

    public function test_chief_and_accountant_dashboards_show_disbursed_amount_stat(): void
    {
        $this->actingAsRole('chief@wdf.go.tz')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.total_disbursed'), false);

        $this->actingAsRole('accountant1@wdf.go.tz')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.total_disbursed'), false);
    }

    public function test_chief_and_accountant_see_simplified_loan_summary_only(): void
    {
        $loan = $this->loanByTrack('WL000010');

        foreach (['chief@wdf.go.tz', 'accountant1@wdf.go.tz'] as $email) {
            $this->actingAsRole($email)
                ->get(route('loan-applications.show', $loan->hashid))
                ->assertOk()
                ->assertSee(__('loans.summary'), false)
                ->assertDontSee(__('loans.applicant_information'), false)
                ->assertDontSee(__('loans.guarantor_information'), false)
                ->assertDontSee(__('loans.approval_history'), false)
                ->assertDontSee(__('loans.supporting_documents'), false);
        }
    }
}
