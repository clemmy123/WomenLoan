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
    }

    public function test_registration_redirects_to_dashboard(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '0712345678',
            'password' => 'password',
            'password_confirmation' => 'password',
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

        $response = $this->actingAs($user)->get(route('applicants.create'));

        $response->assertOk();
        $response->assertSee('value="Anna"', false);
        $response->assertSee('value="Mary"', false);
        $response->assertSee('value="Kimaro"', false);
        $response->assertSee('value="anna.kimaro@example.com"', false);
        $response->assertSee('value="255712345678"', false);
        $response->assertSee('value="712345678"', false);
        $response->assertSee('readonly', false);
        $response->assertSee(__('applicants.registration_fields_locked'), false);
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

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('nav.new_application'), false);
    }

    public function test_profile_create_redirects_when_profile_exists(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('applicants.create'))
            ->assertRedirect(route('applicants.show', $user->applicant));
    }
}
