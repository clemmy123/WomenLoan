<?php

namespace Tests\Feature;

use App\Models\DraftLoan;
use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanDraftTest extends TestCase
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

    public function test_applicant_can_save_loan_application_draft(): void
    {
        $user = \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        $response = $this->actingAs($user)->post(route('loan-applications.store'), [
            'form_action' => 'save_draft',
            'track_id' => 'WL000200',
            'step' => 1,
            'loan_type' => 'individual',
            'region_id' => 1,
            'business_name' => 'Neema Shop',
            'business_phone' => '0712345678',
            'business_email' => 'shop@example.com',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '100-000-001',
        ]);

        $response->assertRedirect(route('loan-applications.create', [
            'resume_track_id' => 'WL000200',
            'wizard_step' => 1,
        ]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('draft_loans', [
            'user_id' => $user->id,
            'track_id' => 'WL000200',
        ]);

        $draft = DraftLoan::where('track_id', 'WL000200')->firstOrFail();
        $this->assertSame('individual', $draft->form_data['loan_type']);
        $this->assertSame('Neema Shop', $draft->form_data['business_name']);
        $this->assertSame(1, (int) $draft->form_data['step']);
    }

    public function test_autosave_draft_returns_stored_documents_and_resume_keeps_them(): void
    {
        $user = \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();
        DraftLoan::where('user_id', $user->id)->delete();

        $response = $this->actingAs($user)->postJson(route('loan-applications.save-draft'), [
            'track_id' => 'WL000213',
            'step' => 1,
            'loan_type' => 'individual',
            'business_name' => 'Preview Shop',
            'business_proposal_document' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'proof_address_attachment' => UploadedFile::fake()->create('address.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'track_id' => 'WL000213',
        ]);

        $documents = $response->json('documents');
        $this->assertArrayHasKey('business_proposal_document', $documents);
        $this->assertArrayHasKey('proof_address_attachment', $documents);
        $this->assertNotSame('', $documents['business_proposal_document']);

        $this->actingAs($user)
            ->get(route('loan-applications.create', [
                'resume_track_id' => 'WL000213',
                'wizard_step' => 6,
            ]))
            ->assertOk()
            ->assertSee('data-has-existing="true"', false)
            ->assertSee($documents['business_proposal_document'], false)
            ->assertSee('Preview Shop', false);
    }

    public function test_applicant_can_resume_saved_draft(): void
    {
        $user = \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        DraftLoan::where('user_id', $user->id)->delete();

        DraftLoan::create([
            'user_id' => $user->id,
            'track_id' => 'WL000201',
            'form_data' => [
                'step' => 2,
                'loan_type' => 'group',
                'business_name' => 'Saved Business',
                'requested_amount' => 1500000,
            ],
        ]);

        $response = $this->actingAs($user)
            ->get(route('loan-applications.create', [
                'resume_track_id' => 'WL000201',
                'wizard_step' => 2,
            ]));

        $response->assertOk();
        $response->assertSee('WL000201', false);
        $response->assertSee('value="group"', false);
        $response->assertSee('Saved Business', false);
        $response->assertSee('value="1500000"', false);
        $response->assertSee(__('loans.draft_status'), false);
        $response->assertSee('"step":2', false);
        $response->assertSee('value="2"', false);
    }

    public function test_applicant_auto_resumes_latest_draft_when_opening_apply(): void
    {
        $user = \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        DraftLoan::where('user_id', $user->id)->delete();

        DraftLoan::create([
            'user_id' => $user->id,
            'track_id' => 'WL000203',
            'form_data' => [
                'step' => 3,
                'loan_type' => 'individual',
                'business_name' => 'Auto Resume Shop',
                'region_id' => 1,
                'district_id' => 1,
                'council_id' => 1,
                'ward_id' => 1,
                'street_id' => 1,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('loan-applications.create'));

        $response->assertRedirect(route('loan-applications.create', [
            'resume_track_id' => 'WL000203',
            'wizard_step' => 3,
        ]));
    }

    public function test_applicant_can_update_existing_draft(): void
    {
        $user = \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        DraftLoan::where('user_id', $user->id)->delete();

        DraftLoan::create([
            'user_id' => $user->id,
            'track_id' => 'WL000202',
            'form_data' => [
                'step' => 1,
                'loan_type' => 'individual',
            ],
        ]);

        $this->actingAs($user)->post(route('loan-applications.store'), [
            'form_action' => 'save_draft',
            'track_id' => 'WL000202',
            'step' => 5,
            'requested_amount' => 2500000,
        ])->assertRedirect(route('loan-applications.create', [
            'resume_track_id' => 'WL000202',
            'wizard_step' => 5,
        ]));

        $draft = DraftLoan::where('track_id', 'WL000202')->firstOrFail();
        $this->assertSame('individual', $draft->form_data['loan_type']);
        $this->assertSame(2500000, (int) $draft->form_data['requested_amount']);
        $this->assertSame(5, (int) $draft->form_data['step']);
    }

    public function test_final_submit_validation_failure_stays_on_submitted_step(): void
    {
        $user = $this->applicantWithoutLoan();
        $trackId = 'WL000211';

        DraftLoan::where('user_id', $user->id)->delete();

        $response = $this->actingAs($user)->from(route('loan-applications.create', [
            'resume_track_id' => $trackId,
            'wizard_step' => 6,
        ]))->post(route('loan-applications.store'), [
            'track_id' => $trackId,
            'step' => 6,
            'loan_type' => 'individual',
            'declaration' => '1',
        ]);

        $response->assertRedirect(route('loan-applications.create', [
            'resume_track_id' => $trackId,
            'wizard_step' => 6,
        ]));
        $response->assertSessionHasErrors('requested_amount');
    }

    public function test_applicant_can_submit_from_draft_without_reuploading_documents(): void
    {
        $user = $this->applicantWithoutLoan();
        $trackId = 'WL000212';

        DraftLoan::where('user_id', $user->id)->delete();

        $documentFields = [
            'business_proposal_document',
            'proof_address_attachment',
            'application_letter',
            'bank_statement',
            'guarantor_letter',
        ];

        $formData = [
            'step' => 6,
            'loan_type' => 'individual',
            'region_id' => 1,
            'district_id' => 1,
            'council_id' => 1,
            'ward_id' => 1,
            'street_id' => 1,
            'business_name' => 'Draft Shop',
            'business_phone' => '0712345678',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '123-456-789',
            'requested_amount' => 500000,
            'bank_name' => 'CRDB Bank',
            'bank_number' => '1234567890',
            'guarantor_first_name' => 'Jane',
            'guarantor_last_name' => 'Guarantor',
            'guarantor_phone' => '0755123456',
            'guarantor_nin' => '19850101123450000001',
            'guarantor_relationship' => 'Husband',
            ...$this->guarantorFields(),
        ];

        foreach ($documentFields as $field) {
            $path = "draft-documents/{$trackId}/{$field}.pdf";
            Storage::disk('public')->put($path, 'pdf-content');
            $formData[$field] = $path;
        }

        DraftLoan::create([
            'user_id' => $user->id,
            'track_id' => $trackId,
            'form_data' => $formData,
        ]);

        $response = $this->actingAs($user)->post(route('loan-applications.store'), [
            'track_id' => $trackId,
            'step' => 6,
            'loan_type' => 'individual',
            'region_id' => 1,
            'district_id' => 1,
            'council_id' => 1,
            'ward_id' => 1,
            'street_id' => 1,
            'business_name' => 'Draft Shop',
            'business_phone' => '0712345678',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '123-456-789',
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
        ]);

        $response->assertRedirect(route('loan-applications.index'));
        $response->assertSessionHas('success');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', $trackId)
            ->firstOrFail();

        $this->assertSame('received', $loan->status);
        $this->assertNotNull($loan->businessDetails->business_proposal_document);
        $this->assertDatabaseMissing('draft_loans', ['track_id' => $trackId]);
    }
}
