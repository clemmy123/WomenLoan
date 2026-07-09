<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\ApprovalLevel;
use App\Models\BusinessDetails;
use App\Models\Council;
use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\Street;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CdoLoanScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_ward_cdo_sees_only_own_ward_loans(): void
    {
        $council = Council::where('name', 'Dodoma Jiji')->firstOrFail();
        $otherWard = Ward::create([
            'name' => 'Makole Test',
            'code' => 'MKL',
            'council_id' => $council->id,
            'district_id' => $council->district_id,
        ]);

        $otherLoan = $this->createLoanInWard($otherWard, 'WL900001');

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertDontSee($otherLoan->loan_track_id, false);
    }

    public function test_two_ward_cdos_on_same_ward_both_see_ward_loans(): void
    {
        $ward = Ward::where('name', 'Tambukareli')->firstOrFail();
        $loan = $this->createLoanInWard($ward, 'WL900002', status: 'received');

        $secondCdo = User::factory()->create([
            'name' => 'Second Ward CDO',
            'email' => 'ward2.cdo@wdf.go.tz',
            'phone' => '255712345099',
            'password' => bcrypt('password'),
            'is_active' => true,
            'zoneable_type' => Ward::class,
            'zoneable_id' => $ward->id,
        ]);
        $secondCdo->assignRole('cdo_ward');

        $this->actingAs($secondCdo)
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertSee($loan->loan_track_id, false);
    }

    public function test_second_ward_cdo_cannot_forward_after_colleague_handled_loan(): void
    {
        $loan = $this->loanByTrack('WL000002');
        $ward = Ward::where('name', 'Tambukareli')->firstOrFail();

        $secondCdo = User::factory()->create([
            'name' => 'Second Ward CDO',
            'email' => 'ward2.cdo@wdf.go.tz',
            'phone' => '255712345099',
            'password' => bcrypt('password'),
            'is_active' => true,
            'zoneable_type' => Ward::class,
            'zoneable_id' => $ward->id,
        ]);
        $secondCdo->assignRole('cdo_ward');

        ApprovalLevel::create([
            'loan_id' => $loan->id,
            'user_id' => User::where('email', 'ward.cdo@wdf.go.tz')->value('id'),
            'step_number' => 1,
            'action_taken' => 'forwarded_to_ministry',
            'comments' => 'Handled by first CDO.',
        ]);

        $this->actingAs($secondCdo)
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'forward_ministry',
                'comments' => 'Should be blocked.',
                'attachment' => UploadedFile::fake()->create('supervision.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_second_ward_cdo_sees_already_handled_badge_after_colleague_acted(): void
    {
        $loan = $this->loanByTrack('WL000002');
        $ward = Ward::where('name', 'Tambukareli')->firstOrFail();

        $secondCdo = User::factory()->create([
            'name' => 'Second Ward CDO',
            'email' => 'ward2.cdo@wdf.go.tz',
            'phone' => '255712345099',
            'password' => bcrypt('password'),
            'is_active' => true,
            'zoneable_type' => Ward::class,
            'zoneable_id' => $ward->id,
        ]);
        $secondCdo->assignRole('cdo_ward');

        ApprovalLevel::create([
            'loan_id' => $loan->id,
            'user_id' => User::where('email', 'ward.cdo@wdf.go.tz')->value('id'),
            'step_number' => 1,
            'action_taken' => 'forwarded_to_ministry',
            'comments' => 'Handled by first CDO.',
        ]);

        $this->actingAs($secondCdo)
            ->get(route('loan-applications.show', $loan->hashid))
            ->assertOk()
            ->assertSee(__('loans.cdo_already_handled'), false)
            ->assertSee(__('loans.cdo_view_only_colleague'), false);
    }

    private function createLoanInWard(
        Ward $ward,
        string $trackId,
        int $step = 1,
        string $status = 'pending',
    ): Loan {
        $street = Street::firstOrCreate(
            ['ward_id' => $ward->id, 'name' => 'Test Street '.$ward->id],
            ['code' => 'TS'.$ward->id],
        );

        $applicant = Applicant::withoutGlobalScopes()->firstOrFail();
        $ward->loadMissing('council.district');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)->create([
            'loan_track_id' => $trackId,
            'user_id' => $applicant->user_id,
            'applicant_id' => $applicant->id,
            'loan_type' => 'individual',
            'requested_amount' => 1500000,
            'proposed_amount' => 0,
            'disbursed_amount' => 0,
            'status' => $status,
            'current_step' => $step,
            'applicant_acceptance' => 'pending',
            'bank_name' => 'CRDB Bank',
            'bank_number' => '0111222333',
        ]);

        BusinessDetails::create([
            'loan_id' => $loan->id,
            'region_id' => $ward->council->district->region_id,
            'district_id' => $ward->district_id,
            'council_id' => $ward->council_id,
            'ward_id' => $ward->id,
            'street_id' => $street->id,
            'business_name' => 'Ward Shop',
            'business_phone' => $applicant->phone,
            'business_email' => $applicant->email,
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '900'.substr($trackId, -7),
            'business_proposal_document' => 'proposals/demo-proposal.pdf',
            'business_registration_attachment' => 'registrations/demo-registration.pdf',
            'proof_address_attachment' => 'proof-of-address/demo-proof.pdf',
            'application_letter' => 'application-letters/demo-letter.pdf',
            'bank_statement' => 'bank-statements/demo-statement.pdf',
        ]);

        return $loan->fresh(['businessDetails.ward']);
    }
}
