<?php

namespace Tests\Feature;

use App\Models\Concerns\HasDisplayName;
use App\Models\Street;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantProfilePrefillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
        // Keep legacy prefill assertions stable; NIDA create UI is covered separately.
        config(['services.nida.enabled' => false]);
    }

    public function test_registration_redirects_to_dashboard(): void
    {
        $response = $this->post(route('register'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '0712345678',
            'password' => $this->strongPassword(),
            'password_confirmation' => $this->strongPassword(),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'john.doe@example.com')->first());
    }

    public function test_split_full_name_handles_common_formats(): void
    {
        $this->assertSame(
            ['first_name' => 'John', 'middle_name' => null, 'last_name' => 'Doe'],
            HasDisplayName::splitFullName('John Doe')
        );

        $this->assertSame(
            ['first_name' => 'Anna', 'middle_name' => 'Mary', 'last_name' => 'Kimaro'],
            HasDisplayName::splitFullName('Anna Mary Kimaro')
        );
    }

    public function test_profile_create_prefills_registration_data(): void
    {
        $this->withoutVite();

        $user = User::factory()->create([
            'name' => 'Anna Mary Kimaro',
            'email' => 'anna.kimaro@example.com',
            'phone' => '0712345678',
        ]);
        $user->assignRole('applicant');

        $profileResponse = $this->actingAs($user)->get(route('profile.show'));

        $profileResponse->assertOk();
        $profileResponse->assertSee('Anna', false);
        $profileResponse->assertSee('Mary', false);
        $profileResponse->assertSee('Kimaro', false);
        $profileResponse->assertSee('anna.kimaro@example.com', false);

        $createResponse = $this->actingAs($user)->get(route('applicants.create'));

        $createResponse->assertOk();
        $createResponse->assertSee(__('applicants.onboarding_loan_type_hint'), false);
        $createResponse->assertSee(__('applicants.loan_types.individual'), false);
        $createResponse->assertSee(__('applicants.loan_types.group'), false);
        $createResponse->assertDontSee(__('applicants.section_identification'), false);
    }

    public function test_profile_store_ignores_tampered_registration_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Anna Mary Kimaro',
            'email' => 'anna.kimaro@example.com',
            'phone' => '0712345678',
        ]);
        $user->assignRole('applicant');

        $response = $this->actingAs($user)->post(route('applicants.store'), [
            'first_name' => 'Changed',
            'middle_name' => 'Fake',
            'last_name' => 'Person',
            'email' => 'fake@example.com',
            'phone' => '0799999999',
            'nin' => '12345678901234567890',
            'dob' => '1990-01-01',
            'sex' => 'Female',
            'preferred_loan_type' => 'individual',
            'has_disability' => '0',
            'marital_status' => 'Single',
            'location_id' => Street::query()->value('id'),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('applicants', [
            'user_id' => $user->id,
            'first_name' => 'Anna',
            'middle_name' => 'Mary',
            'last_name' => 'Kimaro',
            'email' => 'anna.kimaro@example.com',
            'phone' => '255712345678',
        ]);
    }

    public function test_new_application_hidden_in_sidebar_until_profile_complete(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $user->assignRole('applicant');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('nav.register_applicant'), false);
        $response->assertDontSee(route('profile.show').'" class="sidebar-link', false);
        $response->assertDontSee(route('loan-applications.index').'" class="sidebar-link', false);
        $response->assertDontSee(route('loan-applications.create').'" class="sidebar-link', false);
    }

    public function test_profile_create_redirects_when_profile_exists(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('applicants.create'))
            ->assertRedirect(route('applicants.show', $user->applicant));
    }
}
