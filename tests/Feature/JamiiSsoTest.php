<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\JamiiSsoTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class JamiiSsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! config('services.jamii.sso_enabled')) {
            $this->markTestSkipped('Jamii SSO is paused (JAMII_SSO_ENABLED=false).');
        }

        $this->seed([
            \Database\Seeders\RolePermissionSeeder::class,
            \Database\Seeders\StaffUserSeeder::class,
        ]);

        config([
            'services.jamii.shell_url' => 'http://127.0.0.1:5175',
            'services.jamii.cors_origins' => 'http://127.0.0.1:5175,http://localhost:5175',
            'services.jamii.sso_ticket_ttl' => 60,
            'cors.allowed_origins' => [
                'http://127.0.0.1:5175',
                'http://localhost:5175',
            ],
        ]);
    }

    public function test_login_api_issues_one_time_ticket_for_valid_credentials(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://127.0.0.1:5175',
            'Accept' => 'application/json',
        ])->postJson(route('api.jamii.auth.login'), [
            'email' => 'ministry@wdf.go.tz',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['ticket', 'expires_in', 'must_change_password', 'audience', 'modules'])
            ->assertJsonPath('audience', 'staff')
            ->assertJsonPath('modules', ['wdf'])
            ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5175');

        $this->assertNotEmpty($response->json('ticket'));
        $this->assertGreaterThanOrEqual(15, (int) $response->json('expires_in'));
        $this->assertGuest();
    }

    public function test_login_api_rejects_invalid_password(): void
    {
        $response = $this->withHeader('Origin', 'http://127.0.0.1:5175')
            ->postJson(route('api.jamii.auth.login'), [
                'email' => 'ministry@wdf.go.tz',
                'password' => 'wrong-password',
            ]);

        $response->assertUnauthorized()
            ->assertJsonStructure(['message']);
        $this->assertGuest();
    }

    public function test_consume_redirects_to_gateway_even_with_single_module(): void
    {
        $user = User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        $issued = app(JamiiSsoTicketService::class)->issue($user->id, false);
        $next = 'http://127.0.0.1:5175/gateway';

        $response = $this->get(route('auth.sso.consume', [
            'ticket' => $issued['ticket'],
            'next' => $next,
        ]));

        $response->assertRedirect($next);
        $this->assertAuthenticatedAs($user);
    }

    public function test_ticket_cannot_be_reused(): void
    {
        $user = User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        $issued = app(JamiiSsoTicketService::class)->issue($user->id, false);
        $next = 'http://127.0.0.1:5175/gateway';

        $this->get(route('auth.sso.consume', [
            'ticket' => $issued['ticket'],
            'next' => $next,
        ]))->assertRedirect($next);

        Auth::logout();

        $reuse = $this->get(route('auth.sso.consume', [
            'ticket' => $issued['ticket'],
            'next' => $next,
        ]));

        $reuse->assertRedirect('http://127.0.0.1:5175/login?error=sso');
        $this->assertGuest();
    }

    public function test_consume_rejects_non_allowlisted_next_and_uses_default(): void
    {
        $user = User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        $issued = app(JamiiSsoTicketService::class)->issue($user->id, false);

        $response = $this->get(route('auth.sso.consume', [
            'ticket' => $issued['ticket'],
            'next' => 'https://evil.example/phish',
        ]));

        $response->assertRedirect('http://127.0.0.1:5175/gateway');
        $this->assertAuthenticatedAs($user);
    }

    public function test_consume_with_invalid_ticket_redirects_to_shell_login(): void
    {
        $response = $this->get(route('auth.sso.consume', [
            'ticket' => 'not-a-real-ticket',
            'next' => 'http://127.0.0.1:5175/gateway',
        ]));

        $response->assertRedirect('http://127.0.0.1:5175/login?error=sso');
        $this->assertGuest();
    }

    public function test_blade_login_page_redirects_to_jamii_shell(): void
    {
        $this->get(route('login'))
            ->assertRedirect('http://127.0.0.1:5175/login');
    }

    public function test_applicant_consume_also_lands_on_gateway_to_choose_module(): void
    {
        $user = User::factory()->create([
            'email' => 'sso.applicant@wdf.go.tz',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('applicant');

        $issued = app(JamiiSsoTicketService::class)->issue($user->id, false);
        $next = 'http://127.0.0.1:5175/gateway';

        $response = $this->get(route('auth.sso.consume', [
            'ticket' => $issued['ticket'],
            'next' => $next,
        ]));

        $response->assertRedirect($next);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_api_marks_applicant_audience(): void
    {
        $user = User::factory()->create([
            'email' => 'sso.applicant2@wdf.go.tz',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('applicant');

        $response = $this->withHeaders([
            'Origin' => 'http://127.0.0.1:5175',
            'Accept' => 'application/json',
        ])->postJson(route('api.jamii.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('audience', 'applicant')
            ->assertJsonPath('modules', ['wdf']);
    }
}
