<?php

namespace Tests\Feature;

use App\Models\Applicant;
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

        Applicant::withoutGlobalScope(\App\Models\Scopes\ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->update(['has_disability' => true]);

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
            'declaration' => '1',
        ]);

        $response->assertRedirect(route('loan-applications.show', $loan));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame('Updated Shop Name', $loan->businessDetails->business_name);
        $this->assertSame('pending', $loan->status);
        $this->assertSame(1, $loan->businessDetails->district_id);
    }

    public function test_partial_update_preserves_business_location_when_geo_not_sent(): void
    {
        $loan = $this->loanByTrack('WL000001');
        $user = User::findOrFail($loan->user_id);
        $originalDistrictId = $loan->businessDetails->district_id;

        $this->actingAs($user)->put(route('loan-applications.update', $loan), [
            'loan_type' => 'individual',
            'business_name' => 'Partial Update Shop',
            'business_phone' => $loan->businessDetails->business_phone,
            'business_email' => $loan->businessDetails->business_email,
            'business_sector' => $loan->businessDetails->business_sector,
            'business_type' => $loan->businessDetails->business_type,
            'tin_number' => $loan->businessDetails->tin_number,
            'requested_amount' => $loan->requested_amount,
            'declaration' => '1',
        ])->assertRedirect(route('loan-applications.show', $loan));

        $loan->refresh();
        $this->assertSame('Partial Update Shop', $loan->businessDetails->business_name);
        $this->assertSame($originalDistrictId, $loan->businessDetails->district_id);
    }

    public function test_submit_to_ward_requires_business_location(): void
    {
        $loan = $this->loanByTrack('WL000001');
        $user = User::findOrFail($loan->user_id);

        $this->actingAs($user)->put(route('loan-applications.update', $loan), [
            'form_action' => 'submit_to_ward',
            'loan_type' => 'individual',
            'business_name' => $loan->businessDetails->business_name,
            'business_phone' => $loan->businessDetails->business_phone,
            'business_email' => $loan->businessDetails->business_email,
            'business_sector' => $loan->businessDetails->business_sector,
            'business_type' => $loan->businessDetails->business_type,
            'tin_number' => $loan->businessDetails->tin_number,
            'requested_amount' => $loan->requested_amount,
            'declaration' => '1',
        ])->assertSessionHasErrors('district_id');
    }

    public function test_applicant_can_submit_pending_application_to_ward(): void
    {
        $loan = $this->loanByTrack('WL000001');
        $user = User::findOrFail($loan->user_id);
        $guarantor = $loan->guarantors()->firstOrFail();

        $response = $this->actingAs($user)->put(route('loan-applications.update', $loan), [
            'form_action' => 'submit_to_ward',
            'loan_type' => 'individual',
            'region_id' => 1,
            'district_id' => 1,
            'council_id' => 1,
            'ward_id' => 1,
            'street_id' => 1,
            'business_name' => $loan->businessDetails->business_name,
            'business_phone' => $loan->businessDetails->business_phone,
            'business_email' => $loan->businessDetails->business_email,
            'business_sector' => $loan->businessDetails->business_sector,
            'business_type' => $loan->businessDetails->business_type,
            'tin_number' => $loan->businessDetails->tin_number,
            'requested_amount' => $loan->requested_amount,
            'bank_name' => $loan->bank_name,
            'bank_number' => $loan->bank_number,
            'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Mwita',
            'guarantor_phone' => $guarantor->phone,
            'guarantor_nin' => $guarantor->id_number,
            'guarantor_sex' => $guarantor->sex ?? 'Female',
            'guarantor_region_id' => $guarantor->guarantor_region_id,
            'guarantor_district_id' => $guarantor->guarantor_district_id,
            'guarantor_council_id' => $guarantor->guarantor_council_id,
            'guarantor_ward_id' => $guarantor->guarantor_ward_id,
            'guarantor_street_id' => $guarantor->guarantor_street_id,
            'declaration' => '1',
        ]);

        $response->assertRedirect(route('loan-applications.index'));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame('received', $loan->status);

        $this->actingAs($user)
            ->get(route('loan-applications.edit', $loan))
            ->assertForbidden();
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
