<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Gurantor;
use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\Scopes\ApplicantAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function guarantorFields(): array
    {
        return [
            'guarantor_sex' => 'Male',
            'guarantor_region_id' => 1,
            'guarantor_district_id' => 1,
            'guarantor_council_id' => 1,
            'guarantor_ward_id' => 1,
            'guarantor_street_id' => 1,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
        Storage::fake('public');
    }

    public function test_applicant_can_submit_loan_with_guarantor(): void
    {
        $user = $this->applicantWithoutLoan();

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
            'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'has_disability' => '0',
            'is_widowed' => '0',
            'requested_amount' => 500000,
            'bank_name' => 'CRDB Bank',
            'bank_number' => '1234567890',
            'declaration' => '1',
            'guarantor_name' => 'Jane Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000001',
            'guarantor_relationship' => 'Spouse',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('loan-applications.index'));
        $response->assertSessionHas('success');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000300')
            ->firstOrFail();

        $guarantor = Gurantor::where('loan_id', $loan->id)->firstOrFail();
        $this->assertSame('Jane Guarantor', $guarantor->name);
        $this->assertSame('Spouse', $guarantor->relationship);
        $this->assertSame('Male', $guarantor->sex);
        $this->assertSame(1, $guarantor->guarantor_region_id);
        $this->assertSame(1, $guarantor->guarantor_street_id);
        $this->assertFalse($loan->has_disability);
        $this->assertFalse($loan->is_widowed);
        $this->assertNotNull($loan->businessDetails->application_letter);
        $this->assertNotNull($loan->businessDetails->bank_statement);
    }

    public function test_guarantor_relationship_defaults_when_not_provided(): void
    {
        $user = $this->applicantWithoutLoan();

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
            'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'has_disability' => '0',
            'is_widowed' => '0',
            'requested_amount' => 500000,
            'declaration' => '1',
            'guarantor_name' => 'Jane Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000002',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000301')
            ->firstOrFail();

        $this->assertSame('Other', Gurantor::where('loan_id', $loan->id)->value('relationship'));
    }

    public function test_guarantor_relationship_defaults_when_empty_string(): void
    {
        $user = $this->applicantWithoutLoan();

        $this->actingAs($user)->post(route('loan-applications.store'), [
            'track_id' => 'WL000302',
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
            'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'has_disability' => '0',
            'is_widowed' => '0',
            'requested_amount' => 500000,
            'declaration' => '1',
            'guarantor_name' => 'Jane Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000002',
            'guarantor_relationship' => '',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000302')
            ->firstOrFail();

        $this->assertSame('Other', Gurantor::where('loan_id', $loan->id)->value('relationship'));
    }

    public function test_group_loan_requires_group_documents(): void
    {
        $user = $this->applicantWithoutLoan();
        Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->groups()
            ->detach();

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Group Loan Test',
            'leader' => ['age' => 30, 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Grace',
                    'last_name' => 'Moyo',
                    'nin' => '19940101123450000013',
                    'age' => 29,
                    'phone' => '0755666777',
                    'sex' => 'Female',
                    'marital_status' => 'Single',
                ],
            ],
        ]);

        $group = \App\Models\LoanGroup::where('name', 'Group Loan Test')->firstOrFail();

        $this->actingAs($user)->post(route('loan-applications.store'), [
            'track_id' => 'WL000302',
            'loan_type' => 'group',
            'loan_group_id' => $group->id,
            'region_id' => 1,
            'district_id' => 1,
            'council_id' => 1,
            'ward_id' => 1,
            'street_id' => 1,
            'business_name' => 'Group Shop',
            'business_phone' => '0712345678',
            'business_email' => 'group@test.com',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '12345678901',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'group_constitution' => UploadedFile::fake()->create('constitution.pdf', 100, 'application/pdf'),
            'group_muhtasari' => UploadedFile::fake()->create('muhtasari.pdf', 100, 'application/pdf'),
            'group_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'has_disability' => '0',
            'is_widowed' => '1',
            'requested_amount' => 800000,
            'declaration' => '1',
            'guarantor_name' => 'Grace Guarantor',
            'guarantor_phone' => '0755111222',
            'guarantor_nin' => '19940101123450000014',
            'guarantor_relationship' => 'Friend',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('loan-applications.index'));

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000302')
            ->firstOrFail();

        $this->assertSame('group', $loan->loan_type);
        $this->assertSame($group->id, $loan->loan_group_id);
        $this->assertTrue($loan->is_widowed);
        $this->assertNotNull($loan->businessDetails->group_constitution);
        $this->assertNotNull($loan->businessDetails->group_muhtasari);
        $this->assertNotNull($loan->businessDetails->group_certificate);
        $this->assertNotNull($loan->businessDetails->application_letter);
        $this->assertNotNull($loan->businessDetails->bank_statement);
    }

    public function test_loan_submission_requires_all_documents(): void
    {
        $user = $this->applicantWithoutLoan();

        $this->actingAs($user)
            ->post(route('loan-applications.store'), [
                'track_id' => 'WL000303',
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
                'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
                'has_disability' => '0',
                'is_widowed' => '0',
                'requested_amount' => 500000,
                'declaration' => '1',
                'guarantor_name' => 'Jane Guarantor',
                'guarantor_phone' => '0755123456',
                'guarantor_nin' => '19850101123450000003',
                'guarantor_relationship' => 'Spouse',
                ...$this->guarantorFields(),
                'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('business_registration_attachment');
    }

    public function test_loan_submission_rejects_documents_larger_than_one_megabyte(): void
    {
        $user = $this->applicantWithoutLoan();

        $this->actingAs($user)
            ->post(route('loan-applications.store'), [
                'track_id' => 'WL000304',
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
                'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 1025, 'application/pdf'),
                'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
                'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
                'has_disability' => '0',
                'is_widowed' => '0',
                'requested_amount' => 500000,
                'declaration' => '1',
                'guarantor_name' => 'Jane Guarantor',
                'guarantor_phone' => '0755123456',
                'guarantor_nin' => '19850101123450000004',
                'guarantor_relationship' => 'Spouse',
                ...$this->guarantorFields(),
                'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('business_proposal_document');
    }
}
