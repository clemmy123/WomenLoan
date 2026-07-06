<?php

namespace Tests\Feature;

use App\Models\DraftLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
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
            'tin_number' => '10000001',
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

    public function test_applicant_can_resume_saved_draft(): void
    {
        $user = \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

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

    public function test_applicant_can_update_existing_draft(): void
    {
        $user = \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

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
}
