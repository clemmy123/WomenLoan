<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NidaRqVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.nida.enabled', true);
        Config::set('services.nida.driver', 'fake');
    }

    public function test_rq_verification_flow_returns_female_identity(): void
    {
        $nin = '19920515123456789012';

        $start = $this->postJson(route('nida.api.start'), ['nin' => $nin])
            ->assertOk()
            ->assertJsonPath('data.completed', false)
            ->assertJsonPath('data.rq_code', 'RQ001');

        $sessionId = $start->json('data.session_id');

        $this->postJson(route('nida.api.answer'), [
            'nin' => $nin,
            'session_id' => $sessionId,
            'rq_code' => 'RQ001',
            'answer' => 'Asha',
        ])
            ->assertOk()
            ->assertJsonPath('data.rq_code', 'RQ002')
            ->assertJsonPath('data.previous_answer_code', 123);

        $this->postJson(route('nida.api.answer'), [
            'nin' => $nin,
            'session_id' => $sessionId,
            'rq_code' => 'RQ002',
            'answer' => 'Dodoma',
        ])
            ->assertOk()
            ->assertJsonPath('data.completed', true)
            ->assertJsonPath('data.first_name', 'Neema')
            ->assertJsonPath('data.sex', 'Female')
            ->assertJsonPath('data.nationality', 'Tanzanian');
    }

    public function test_wrong_answer_returns_code_124(): void
    {
        $nin = '19920515123456789012';

        $start = $this->postJson(route('nida.api.start'), ['nin' => $nin])->assertOk();
        $sessionId = $start->json('data.session_id');

        $this->postJson(route('nida.api.answer'), [
            'nin' => $nin,
            'session_id' => $sessionId,
            'rq_code' => 'RQ001',
            'answer' => 'Wrong',
        ])
            ->assertOk()
            ->assertJsonPath('data.previous_answer_code', 124)
            ->assertJsonPath('data.completed', false);
    }

    public function test_register_page_renders_nida_wizard(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('nidaRegisterWizard', false)
            ->assertSee(__('nida.step_nin'), false);
    }

    public function test_applicant_create_page_shows_nin_as_constant(): void
    {
        $this->withoutVite();
        $this->seedApplication();

        $user = \App\Models\User::factory()->create([
            'first_name' => 'Neema',
            'middle_name' => 'Juma',
            'last_name' => 'Mwangi',
            'name' => 'Neema Juma Mwangi',
            'nin' => '19920515123456789012',
            'dob' => '1992-05-15',
            'sex' => 'Female',
            'nationality' => 'Tanzanian',
            'nida_verified_at' => now(),
        ]);
        $user->assignRole('applicant');

        $this->actingAs($user)
            ->get(route('applicants.create'))
            ->assertOk()
            ->assertDontSee('nidaApplicantWizard', false)
            ->assertSee(__('nida.nida_fields_locked'), false)
            ->assertSee('19920515-12345-67890-12', false)
            ->assertSee('Neema', false);
    }

    public function test_admin_create_page_keeps_manual_editable_form(): void
    {
        $this->withoutVite();
        $this->seedApplication();

        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get(route('applicants.create'))
            ->assertOk()
            ->assertSee(__('applicants.add_new'), false)
            ->assertSee(__('applicants.manual_mode'), false)
            ->assertDontSee(__('nida.nida_fields_locked'), false)
            ->assertSee('name="nin"', false)
            ->assertSee('name="dob"', false);
    }
}
