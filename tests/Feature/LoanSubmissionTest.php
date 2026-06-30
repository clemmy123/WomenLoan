<?php

namespace Tests\Feature;

use App\Models\Gurantor;
use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
        Storage::fake('public');
    }

    public function test_applicant_can_submit_loan_with_guarantor(): void
    {
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        $response = $this->actingAs($user)->post(route('loan-applications.store'), [
            'track_id' => 'WL000300',
            'loan_type' => 'individual',
            'region_id' => 1,
            'district_id' => 1,
            'council_id' => 1,
            'ward_id' => 1,
            'street_id' => 1,
            'business_name' => 'Test Shop',
            'business_phone' => '0712345678',
            'business_email' => 'shop@test.com',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '12345678901',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'requested_amount' => 500000,
            'bank_name' => 'CRDB',
            'bank_number' => '1234567890',
            'declaration' => '1',
            'guarantor_name' => 'Jane Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000001',
            'guarantor_relationship' => 'Spouse',
        ]);

        $response->assertRedirect(route('loan-applications.index'));
        $response->assertSessionHas('success');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000300')
            ->firstOrFail();

        $guarantor = Gurantor::where('loan_id', $loan->id)->firstOrFail();
        $this->assertSame('Jane Guarantor', $guarantor->name);
        $this->assertSame('Spouse', $guarantor->relationship);
    }

    public function test_guarantor_relationship_defaults_when_not_provided(): void
    {
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)->post(route('loan-applications.store'), [
            'track_id' => 'WL000301',
            'loan_type' => 'individual',
            'region_id' => 1,
            'district_id' => 1,
            'council_id' => 1,
            'ward_id' => 1,
            'street_id' => 1,
            'business_name' => 'Test Shop',
            'business_phone' => '0712345678',
            'business_email' => 'shop@test.com',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '12345678901',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'requested_amount' => 500000,
            'declaration' => '1',
            'guarantor_name' => 'Jane Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000002',
        ])->assertRedirect();

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000301')
            ->firstOrFail();

        $this->assertSame('Other', Gurantor::where('loan_id', $loan->id)->value('relationship'));
    }
}
