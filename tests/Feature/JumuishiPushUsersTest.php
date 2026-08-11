<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Jumuishi\JumuishiCentralUserSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JumuishiPushUsersTest extends TestCase
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
        ]);

        $this->seed([\Database\Seeders\RolePermissionSeeder::class]);
    }

    public function test_push_user_sets_global_user_id_from_jumuishi(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@wdf.go.tz',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('super_admin');

        Http::fake([
            'http://127.0.0.1:8000/api/internal/users/sync' => Http::response([
                'status' => 'success',
                'data' => [
                    'global_user_id' => 501,
                    'created' => true,
                ],
            ], 201),
        ]);

        $result = app(JumuishiCentralUserSync::class)->push($user->fresh(), true);

        $this->assertSame(501, $result['global_user_id']);
        $this->assertSame(501, $user->fresh()->global_user_id);
        $this->assertSame('synced', $user->fresh()->jumuishi_sync_status);

        Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'http://127.0.0.1:8000/api/internal/users/sync'
                && $request['email'] === 'admin@wdf.go.tz'
                && $request['local_user_id'] === (string) $user->id
                && $request['sync_password'] === true
                && $request->header('X-Jumuishi-Module')[0] === 'women-loans';
        });
    }
}
