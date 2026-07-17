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

    protected function nidaSession(): static
    {
        return $this->withSession(['nida_registration_allowed' => true]);
    }

    public function test_rq_verification_flow_returns_female_identity(): void
    {
        $nin = '19920515123456789012';

        $start = $this->nidaSession()->postJson(route('nida.api.start'), ['nin' => $nin])
            ->assertOk()
            ->assertJsonPath('data.completed', false)
            ->assertJsonPath('data.rq_code', 'RQ001');

        $sessionId = $start->json('data.session_id');

        $this->nidaSession()->postJson(route('nida.api.answer'), [
            'nin' => $nin,
            'session_id' => $sessionId,
            'rq_code' => 'RQ001',
            'answer' => 'Asha',
        ])
            ->assertOk()
            ->assertJsonPath('data.rq_code', 'RQ002')
            ->assertJsonPath('data.previous_answer_code', 123);

        $verified = $this->nidaSession()->postJson(route('nida.api.answer'), [
            'nin' => $nin,
            'session_id' => $sessionId,
            'rq_code' => 'RQ002',
            'answer' => 'Dodoma',
        ])
            ->assertOk()
            ->assertJsonPath('data.completed', true)
            ->assertJsonPath('data.sex', 'Female')
            ->assertJsonPath('data.nationality', 'Tanzanian');

        $firstName = $verified->json('data.first_name');
        $this->assertIsString($firstName);
        $this->assertNotSame('', $firstName);
        $this->assertSame($firstName, $verified->json('data.first_name'));
    }

    public function test_different_nins_yield_different_demo_names(): void
    {
        $names = [];

        foreach (['19920515123456789012', '19881101111111111111', '19991231999999999999'] as $nin) {
            $start = $this->nidaSession()->postJson(route('nida.api.start'), ['nin' => $nin])->assertOk();
            $sessionId = $start->json('data.session_id');

            $this->nidaSession()->postJson(route('nida.api.answer'), [
                'nin' => $nin,
                'session_id' => $sessionId,
                'rq_code' => 'RQ001',
                'answer' => 'Asha',
            ])->assertOk();

            $verified = $this->nidaSession()->postJson(route('nida.api.answer'), [
                'nin' => $nin,
                'session_id' => $sessionId,
                'rq_code' => 'RQ002',
                'answer' => 'Dodoma',
            ])->assertOk();

            $names[] = implode(' ', [
                $verified->json('data.first_name'),
                $verified->json('data.middle_name'),
                $verified->json('data.last_name'),
            ]);
        }

        $this->assertSame(3, count(array_unique($names)), 'Expected varied demo identities across NINs.');
    }

    public function test_wrong_answer_returns_code_124(): void
    {
        $nin = '19920515123456789012';

        $start = $this->nidaSession()->postJson(route('nida.api.start'), ['nin' => $nin])->assertOk();
        $sessionId = $start->json('data.session_id');

        $this->nidaSession()->postJson(route('nida.api.answer'), [
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
