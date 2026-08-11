<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class JumuishiModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jumuishi.enabled' => true,
            'jumuishi.url' => 'http://127.0.0.1:8000',
            'jumuishi.module_path' => 'women-loans',
            'jumuishi.api_secret' => 'module-secret-for-tests',
            'jumuishi.platform_secret' => 'platform-secret-for-tests',
            'jumuishi.sso_exchange_path' => '/api/internal/sso/exchange',
        ]);

        $this->seed([
            \Database\Seeders\RolePermissionSeeder::class,
        ]);
    }

    public function test_health_endpoint_reports_success(): void
    {
        $this->getJson(route('api.jumuishi.health'))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('database', 'connected');
    }

    public function test_queue_health_endpoint_reports_worker_status(): void
    {
        $this->getJson(route('api.jumuishi.queue-health'))
            ->assertOk()
            ->assertJsonStructure([
                'worker_status',
                'pending_jobs',
                'failed_jobs',
                'connection_name',
            ]);
    }

    public function test_provision_requires_platform_secret(): void
    {
        $this->postJson(route('api.jumuishi.users.provision'), [
            'global_user_id' => 10,
            'email' => 'central@example.com',
            'event_uuid' => (string) Str::uuid(),
        ])->assertUnauthorized();
    }

    public function test_provision_upserts_user_and_is_idempotent(): void
    {
        $eventUuid = (string) Str::uuid();
        $hash = Hash::make('CentralPass1!');

        $payload = [
            'global_user_id' => 42,
            'email' => 'Central.User@Example.com',
            'first_name' => 'Asha',
            'second_name' => 'B',
            'last_name' => 'Juma',
            'gender' => 'female',
            'password_hash' => $hash,
            'status' => 'active',
            'event_uuid' => $eventUuid,
            'event_type' => 'user.created',
        ];

        $first = $this->withHeader('X-Jumuishi-Platform-Secret', 'platform-secret-for-tests')
            ->postJson(route('api.jumuishi.users.provision'), $payload)
            ->assertOk()
            ->json('data.local_user_id');

        $second = $this->withHeader('X-Jumuishi-Platform-Secret', 'platform-secret-for-tests')
            ->postJson(route('api.jumuishi.users.provision'), $payload)
            ->assertOk()
            ->json('data.local_user_id');

        $this->assertSame($first, $second);
        $this->assertSame(1, User::query()->where('global_user_id', 42)->count());

        $user = User::query()->where('global_user_id', 42)->firstOrFail();
        $this->assertSame('central.user@example.com', $user->email);
        $this->assertTrue($user->hasRole('applicant'));
        $this->assertTrue(Hash::check('CentralPass1!', $user->password));
    }

    public function test_sync_disables_and_enables_user(): void
    {
        $user = User::factory()->create([
            'email' => 'sync.me@example.com',
            'global_user_id' => 77,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('applicant');

        $this->withHeader('X-Jumuishi-Platform-Secret', 'platform-secret-for-tests')
            ->postJson(route('api.jumuishi.users.sync'), [
                'event_uuid' => (string) Str::uuid(),
                'event_type' => 'user.disabled',
                'global_user_id' => 77,
                'email' => 'sync.me@example.com',
                'status' => 'disabled',
            ])
            ->assertOk();

        $this->assertFalse($user->fresh()->is_active);

        $this->withHeader('X-Jumuishi-Platform-Secret', 'platform-secret-for-tests')
            ->postJson(route('api.jumuishi.users.sync'), [
                'event_uuid' => (string) Str::uuid(),
                'event_type' => 'user.enabled',
                'global_user_id' => 77,
                'email' => 'sync.me@example.com',
                'status' => 'active',
            ])
            ->assertOk();

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_sso_consume_exchanges_ticket_and_logs_in(): void
    {
        $ticket = str_repeat('a', 64);

        Http::fake([
            'http://127.0.0.1:8000/api/internal/sso/exchange' => Http::response([
                'status' => 'success',
                'data' => [
                    'global_user_id' => 99,
                    'email' => 'sso.user@example.com',
                    'first_name' => 'Sso',
                    'second_name' => null,
                    'last_name' => 'User',
                    'gender' => 'female',
                    'status' => 'active',
                ],
            ], 200),
        ]);

        $this->get(route('jumuishi.sso.consume', [
            'ticket' => $ticket,
            'return_to' => '/dashboard',
        ]))->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $user = User::query()->where('global_user_id', 99)->firstOrFail();
        $this->assertAuthenticatedAs($user);

        Http::assertSent(function ($request) use ($ticket) {
            return $request->url() === 'http://127.0.0.1:8000/api/internal/sso/exchange'
                && $request['ticket'] === $ticket
                && $request->header('X-Jumuishi-Module')[0] === 'women-loans'
                && $request->header('X-Jumuishi-Secret')[0] === 'module-secret-for-tests';
        });
    }

    public function test_sso_consume_rejects_invalid_ticket_length(): void
    {
        $this->get(route('jumuishi.sso.consume', [
            'ticket' => 'short',
            'return_to' => '/dashboard',
        ]))->assertRedirect('http://127.0.0.1:8000/login');

        $this->assertGuest();
    }
}
