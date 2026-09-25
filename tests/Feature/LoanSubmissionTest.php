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
            'tin_number' => '123-456-789',
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
            'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000001',
            'guarantor_relationship' => 'Husband',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('loan-applications.index'));
        $response->assertSessionHas('success');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000300')
            ->firstOrFail();

        $guarantor = Gurantor::where('loan_id', $loan->id)->firstOrFail();
        $this->assertSame('Jane', $guarantor->first_name);
        $this->assertSame('Guarantor', $guarantor->last_name);
        $this->assertSame('Jane Guarantor', $guarantor->name);
        $this->assertSame('received', $loan->status);
        $this->assertSame('Husband', $guarantor->relationship);
        $this->assertSame('Male', $guarantor->sex);
        $this->assertSame(1, $guarantor->guarantor_region_id);
        $this->assertSame(1, $guarantor->guarantor_street_id);
        $this->assertFalse($loan->has_disability);
        $this->assertFalse($loan->is_widowed);
        $this->assertNotNull($loan->businessDetails->application_letter);
        $this->assertNotNull($loan->businessDetails->bank_statement);
    }

    public function test_individual_loan_requires_guarantor_relationship(): void
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
            'tin_number' => '123-456-789',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'has_disability' => '0',
            'is_widowed' => '0',
            'requested_amount' => 500000,
            'declaration' => '1',
            'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000002',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('guarantor_relationship');

        $this->assertNull(
            Loan::withoutGlobalScope(ApprovalLevelScope::class)
                ->where('loan_track_id', 'WL000301')
                ->first()
        );
    }

    public function test_individual_loan_rejects_invalid_guarantor_relationship(): void
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
            'tin_number' => '123-456-789',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'has_disability' => '0',
            'is_widowed' => '0',
            'requested_amount' => 500000,
            'declaration' => '1',
            'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000002',
            'guarantor_relationship' => 'Spouse',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('guarantor_relationship');
    }

    public function test_individual_apply_form_shows_guarantor_relationship_dropdown(): void
    {
        $user = $this->applicantWithoutLoan();
        \App\Models\DraftLoan::where('user_id', $user->id)->delete();

        $response = $this->actingAs($user)->get(route('loan-applications.create'));

        $response->assertOk();
        $response->assertSee('name="guarantor_relationship"', false);

        foreach (['Father', 'Mother', 'Brother', 'Sister', 'Child', 'Husband', 'Friend'] as $value) {
            $response->assertSee('value="'.$value.'"', false);
        }
    }

    public function test_group_loan_requires_group_documents(): void
    {
        $user = $this->applicantWithoutLoan();
        Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->groups()
            ->detach();

        Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->update([
                'preferred_loan_type' => 'group',
                'marital_status' => 'Widowed',
            ]);

        $applicant = Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Group Loan Test',
            'leader' => ['dob' => $applicant->dob->format('Y-m-d'), 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Grace',
                    'last_name' => 'Moyo',
                    'nin' => '19940101123450000013',
                    'dob' => '1994-01-01',
                    'phone' => '0755666777',
                    'sex' => 'Female',
                    'marital_status' => 'Single',
                ],
            ],
        ]);

        $group = \App\Models\LoanGroup::where('name', 'Group Loan Test')->firstOrFail();

        \App\Models\DraftLoan::where('user_id', $user->id)->delete();

        $this->actingAs($user)
            ->get(route('loan-applications.create'))
            ->assertOk()
            ->assertDontSee('name="guarantor_relationship"', false);

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
            'tin_number' => '123-456-789',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'group_constitution' => UploadedFile::fake()->create('constitution.pdf', 100, 'application/pdf'),
            'group_muhtasari' => UploadedFile::fake()->create('muhtasari.pdf', 100, 'application/pdf'),
            'group_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'requested_amount' => 800000,
            'declaration' => '1',
            'guarantor_first_name' => 'Grace',
            'guarantor_last_name' => 'Guarantor',
            'guarantor_phone' => '0755111222',
            'guarantor_nin' => '19940101123450000014',
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
        $this->assertSame('Guarantor', Gurantor::where('loan_id', $loan->id)->value('relationship'));
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
                'tin_number' => '123-456-789',
                'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
                'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
                'has_disability' => '0',
                'is_widowed' => '0',
                'requested_amount' => 500000,
                'declaration' => '1',
                'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
                'guarantor_phone' => '0755123456',
                'guarantor_nin' => '19850101123450000003',
                'guarantor_relationship' => 'Husband',
                ...$this->guarantorFields(),
                'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('proof_address_attachment');
    }

    public function test_loan_submission_allows_optional_business_license(): void
    {
        $user = $this->applicantWithoutLoan();

        $response = $this->actingAs($user)
            ->post(route('loan-applications.store'), [
                'track_id' => 'WL000303B',
                'loan_type' => 'individual',
                'region_id' => 1,
                'district_id' => 1,
                'council_id' => 1,
                'ward_id' => 1,
                'street_id' => 1,
                'business_name' => 'Test Shop',
                'business_phone' => '0712345678',
                'business_sector' => 'Trade',
                'business_type' => 'Retail',
                'tin_number' => '123-456-790',
                'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
                'proof_address_attachment' => UploadedFile::fake()->create('address.pdf', 100, 'application/pdf'),
                'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
                'has_disability' => '0',
                'is_widowed' => '0',
                'requested_amount' => 500000,
                'bank_name' => 'CRDB Bank',
                'bank_number' => '1234567890',
                'declaration' => '1',
                'guarantor_first_name' => 'Jane',
                'guarantor_last_name' => 'Guarantor',
                'guarantor_phone' => '0755123456',
                'guarantor_nin' => '19850101123450000003',
                'guarantor_relationship' => 'Husband',
                ...$this->guarantorFields(),
                'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('loan-applications.index'));
        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors('business_registration_attachment');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000303B')
            ->firstOrFail();

        $this->assertNull($loan->businessDetails->business_registration_attachment);
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
                'tin_number' => '123-456-789',
                'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 1025, 'application/pdf'),
                'business_registration_attachment' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
                'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
                'has_disability' => '0',
                'is_widowed' => '0',
                'requested_amount' => 500000,
                'declaration' => '1',
                'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
                'guarantor_phone' => '0755123456',
                'guarantor_nin' => '19850101123450000004',
                'guarantor_relationship' => 'Husband',
                ...$this->guarantorFields(),
                'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('business_proposal_document');
    }

    public function test_tin_number_must_be_unique_across_applications(): void
    {
        $existingTin = \App\Models\BusinessDetails::query()->value('tin_number');
        $this->assertNotNull($existingTin);

        $user = $this->applicantWithoutLoan();

        $this->actingAs($user)
            ->post(route('loan-applications.store'), [
                'track_id' => 'WL000305',
                'loan_type' => 'individual',
                'region_id' => 1,
                'district_id' => 1,
                'council_id' => 1,
                'ward_id' => 1,
                'street_id' => 1,
                'business_name' => 'Duplicate Tin Shop',
                'business_phone' => '0712345678',
                'business_email' => 'duplicate-tin@test.com',
                'business_sector' => 'Trade',
                'business_type' => 'Retail',
                'tin_number' => $existingTin,
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
                'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
                'guarantor_phone' => '0755123456',
                'guarantor_nin' => '19850101123450000005',
                'guarantor_relationship' => 'Husband',
                ...$this->guarantorFields(),
                'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('tin_number');
    }

    public function test_missing_guarantor_phone_returns_validation_error_not_server_error(): void
    {
        $user = $this->applicantWithoutLoan();

        $response = $this->actingAs($user)->from(route('loan-applications.create'))
            ->post(route('loan-applications.store'), [
                'track_id' => 'WL000306',
                'step' => 2,
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
                'tin_number' => '998-877-665',
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
                'guarantor_first_name' => 'Jane',
                'guarantor_last_name' => 'Guarantor',
                'guarantor_nin' => '19850101123450000006',
                'guarantor_relationship' => 'Husband',
                ...$this->guarantorFields(),
                'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('loan-applications.create', [
            'resume_track_id' => 'WL000306',
            'wizard_step' => 2,
        ]));
        $response->assertSessionHasErrors('guarantor_phone');
        $response->assertSessionDoesntHaveErrors('error');

        $this->actingAs($user)
            ->get($response->headers->get('Location'))
            ->assertOk()
            ->assertDontSee(__('messages.unexpected_error'), false);
    }

    public function test_missing_business_type_stays_on_submitted_step(): void
    {
        $user = $this->applicantWithoutLoan();
        $trackId = 'WL000307';

        $response = $this->actingAs($user)->from(route('loan-applications.create', [
            'resume_track_id' => $trackId,
            'wizard_step' => 6,
        ]))->post(route('loan-applications.store'), [
            'track_id' => $trackId,
            'step' => 6,
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
            'tin_number' => '998-877-666',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'has_disability' => '0',
            'is_widowed' => '0',
            'requested_amount' => 500000,
            'declaration' => '1',
            'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000007',
            'guarantor_relationship' => 'Husband',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('loan-applications.create', [
            'resume_track_id' => $trackId,
            'wizard_step' => 6,
        ]));
        $response->assertSessionHasErrors('business_type');
        $response->assertSessionDoesntHaveErrors('error');
    }

    public function test_invalid_business_phone_stays_on_phone_step(): void
    {
        $user = $this->applicantWithoutLoan();

        $response = $this->actingAs($user)->from(route('loan-applications.create'))
            ->post(route('loan-applications.store'), [
                'track_id' => 'WL000308',
                'step' => 1,
                'loan_type' => 'individual',
                'region_id' => 1,
                'district_id' => 1,
                'council_id' => 1,
                'ward_id' => 1,
                'street_id' => 1,
                'business_name' => 'Test Shop',
                'business_phone' => '255877777777',
                'business_email' => 'shop@test.com',
                'business_sector' => 'Trade',
                'business_type' => 'Retail',
                'tin_number' => '321-654-987',
                'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
                'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
                'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
                'has_disability' => '0',
                'is_widowed' => '0',
                'requested_amount' => 500000,
                'declaration' => '1',
                'guarantor_first_name' => 'Jane',
                'guarantor_last_name' => 'Guarantor',
                'guarantor_phone' => '0755999111',
                'guarantor_nin' => '19850101123450000099',
                'guarantor_relationship' => 'Husband',
                ...$this->guarantorFields(),
                'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('loan-applications.create', [
            'resume_track_id' => 'WL000308',
            'wizard_step' => 1,
        ]));
        $response->assertSessionHasErrors('business_phone');
        $response->assertSessionDoesntHaveErrors('error');

        $this->actingAs($user)
            ->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee(__('validation.custom.business_phone.phone'), false);
    }

    public function test_invalid_business_phone_from_review_stays_on_review_step(): void
    {
        $user = $this->applicantWithoutLoan();
        $trackId = 'WL000309';

        $response = $this->actingAs($user)->from(route('loan-applications.create', [
            'resume_track_id' => $trackId,
            'wizard_step' => 6,
        ]))->post(route('loan-applications.store'), [
            'track_id' => $trackId,
            'step' => 6,
            'loan_type' => 'individual',
            'region_id' => 1,
            'district_id' => 1,
            'council_id' => 1,
            'ward_id' => 1,
            'street_id' => 1,
            'business_name' => 'Test Shop',
            'business_phone' => '255877777777',
            'business_email' => 'shop@test.com',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '321-654-987',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('proof-address.pdf', 100, 'application/pdf'),
            'application_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'bank_statement' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            'has_disability' => '0',
            'is_widowed' => '0',
            'requested_amount' => 500000,
            'declaration' => '1',
            'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
            'guarantor_phone' => '0755999111',
            'guarantor_nin' => '19850101123450000088',
            'guarantor_relationship' => 'Husband',
            ...$this->guarantorFields(),
            'guarantor_letter' => UploadedFile::fake()->create('guarantor-letter.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('loan-applications.create', [
            'resume_track_id' => $trackId,
            'wizard_step' => 6,
        ]));
        $response->assertSessionHasErrors('business_phone');
    }

    public function test_empty_business_fields_show_specific_messages(): void
    {
        $user = $this->applicantWithoutLoan();

        $response = $this->actingAs($user)
            ->from(route('loan-applications.create'))
            ->post(route('loan-applications.store'), [
                'track_id' => 'WL000401',
                'loan_type' => 'individual',
                'declaration' => '1',
            ]);

        $response->assertSessionHasErrors([
            'business_name' => __('validation.custom.business_name.required'),
            'region_id' => __('validation.custom.region_id.required'),
            'business_phone' => __('validation.custom.business_phone.required'),
            'business_sector' => __('validation.custom.business_sector.required'),
        ]);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee(__('validation.custom.business_name.required'), false)
            ->assertSee(__('validation.custom.region_id.required'), false);
    }
}
