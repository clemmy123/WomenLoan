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
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        $response = $this->actingAs($user)->post(route('loan-applications.store'), [
            'form_action' => 'save_draft',
            'track_id' => 'WL000200',
            'step' => 2,
            'loan_type' => 'individual',
            'region_id' => 1,
            'business_name' => 'Neema Shop',
            'business_phone' => '0712345678',
            'business_email' => 'shop@example.com',
            'business_sector' => 'Trade',
            'business_type' => 'Retail',
            'tin_number' => '10000001',
        ]);

        $response->assertRedirect(route('loan-applications.create', ['resume_track_id' => 'WL000200']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('draft_loans', [
            'user_id' => $user->id,
            'track_id' => 'WL000200',
        ]);

        $draft = DraftLoan::where('track_id', 'WL000200')->firstOrFail();
        $this->assertSame('individual', $draft->form_data['loan_type']);
        $this->assertSame('Neema Shop', $draft->form_data['business_name']);
        $this->assertSame(2, (int) $draft->form_data['step']);
    }

    public function test_applicant_can_resume_saved_draft(): void
    {
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        DraftLoan::create([
            'user_id' => $user->id,
            'track_id' => 'WL000201',
            'form_data' => [
                'step' => 3,
                'loan_type' => 'group',
                'business_name' => 'Saved Business',
                'requested_amount' => 1500000,
            ],
        ]);

        $response = $this->actingAs($user)
            ->get(route('loan-applications.create', ['resume_track_id' => 'WL000201']));

        $response->assertOk();
        $response->assertSee('WL000201', false);
        $response->assertSee('value="group"', false);
        $response->assertSee('Saved Business', false);
        $response->assertSee('value="1500000"', false);
    }

    public function test_applicant_can_update_existing_draft(): void
    {
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

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
            'loan_type' => 'group',
            'requested_amount' => 2500000,
        ])->assertRedirect();

        $draft = DraftLoan::where('track_id', 'WL000202')->firstOrFail();
        $this->assertSame('group', $draft->form_data['loan_type']);
        $this->assertSame(2500000, (int) $draft->form_data['requested_amount']);
        $this->assertSame(5, (int) $draft->form_data['step']);
    }
}
