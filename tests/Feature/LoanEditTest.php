<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_applicant_can_edit_pending_application(): void
    {
        $loan = $this->loanByTrack('WL000001');
        $user = User::findOrFail($loan->user_id);

        $response = $this->actingAs($user)
            ->get(route('loan-applications.edit', $loan));

        $response->assertOk();
        $response->assertSee(__('loans.edit_title'));
        $response->assertSee('"selectedRegion":"'.$loan->businessDetails->region_id.'"', false);
        $response->assertSee('"selectedDistrict":"'.$loan->businessDetails->district_id.'"', false);
        $response->assertSee('"selectedStreet":"'.$loan->businessDetails->street_id.'"', false);
    }

    public function test_applicant_cannot_edit_received_application(): void
    {
        $loan = $this->loanByTrack('WL000002');
        $user = User::findOrFail($loan->user_id);

        $this->actingAs($user)
            ->get(route('loan-applications.edit', $loan))
            ->assertForbidden();
    }

    public function test_applicant_cannot_edit_once_processing_started(): void
    {
        $loan = $this->loanByTrack('WL000003');
        $user = User::findOrFail($loan->user_id);

        $this->actingAs($user)
            ->get(route('loan-applications.edit', $loan))
            ->assertForbidden();
    }

    public function test_applicant_can_update_pending_application(): void
    {
        $loan = $this->loanByTrack('WL000001');
        $user = User::findOrFail($loan->user_id);

        $response = $this->actingAs($user)->put(route('loan-applications.update', $loan), [
            'loan_type' => 'individual',
            'region_id' => 1,
            'district_id' => 1,
            'council_id' => 1,
            'ward_id' => 1,
            'street_id' => 1,
            'business_name' => 'Updated Shop Name',
            'business_phone' => '0712345678',
            'business_email' => 'updated@test.com',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '12345678901',
            'requested_amount' => 4200000,
            'bank_name' => 'NMB Bank Plc (National Microfinance Bank)',
            'bank_number' => '9876543210',
            'has_disability' => '1',
            'is_widowed' => '0',
            'declaration' => '1',
        ]);

        $response->assertRedirect(route('loan-applications.show', $loan));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame('4200000.00', $loan->requested_amount);
        $this->assertSame('Updated Shop Name', $loan->businessDetails->business_name);
        $this->assertSame('NMB Bank Plc (National Microfinance Bank)', $loan->bank_name);
        $this->assertTrue($loan->has_disability);
        $this->assertFalse($loan->is_widowed);
    }

    public function test_update_blocked_when_processing_started(): void
    {
        $loan = $this->loanByTrack('WL000003');
        $user = User::findOrFail($loan->user_id);

        $this->actingAs($user)->put(route('loan-applications.update', $loan), [
            'loan_type' => 'individual',
            'business_name' => 'Should Not Update',
            'business_phone' => '0712345678',
            'business_email' => 'shop@test.com',
            'requested_amount' => 1000000,
            'declaration' => '1',
        ])->assertForbidden();

        $loan->refresh();
        $this->assertNotSame('Should Not Update', $loan->businessDetails->business_name);
    }

    public function test_other_applicant_cannot_edit_loan(): void
    {
        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000001')
            ->firstOrFail();

        $other = User::where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($other)
            ->get(route('loan-applications.edit', $loan))
            ->assertNotFound();
    }
}
