<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LoginLockoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            \Database\Seeders\RolePermissionSeeder::class,
            \Database\Seeders\StaffUserSeeder::class,
        ]);
    }

    public function test_three_failed_attempts_lock_account_for_five_minutes(): void
    {
        $user = User::where('email', 'ministry@wdf.go.tz')->firstOrFail();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-pass-1'])
            ->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-pass-2'])
            ->assertSessionHasErrors('email');

        $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-pass-3']);
        $response->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertNotNull($user->login_locked_until);
        $this->assertTrue($user->login_locked_until->isFuture());
        $this->assertSame(1, (int) $user->login_lockout_rounds);
        $this->assertFalse((bool) $user->login_locked_permanently);
    }

    public function test_second_round_of_failures_locks_account_permanently(): void
    {
        $user = User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        $lockout = app(LoginLockoutService::class);

        // First round → temp lock
        $lockout->registerFailure($user->fresh());
        $lockout->registerFailure($user->fresh());
        $lockout->registerFailure($user->fresh());

        $user->refresh();
        $this->assertTrue($lockout->isTemporarilyLocked($user));

        // Expire temp lock
        $user->forceFill(['login_locked_until' => now()->subMinute()])->save();

        // Second round → permanent
        $lockout->registerFailure($user->fresh());
        $lockout->registerFailure($user->fresh());
        $result = $lockout->registerFailure($user->fresh());

        $this->assertTrue($result['permanently_locked']);
        $user->refresh();
        $this->assertTrue((bool) $user->login_locked_permanently);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.locked_permanently')]);
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        $user = User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        $user->forceFill([
            'failed_login_attempts' => 2,
            'login_lockout_rounds' => 0,
        ])->save();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertSame(0, (int) $user->failed_login_attempts);
        $this->assertSame(0, (int) $user->login_lockout_rounds);
        $this->assertNull($user->login_locked_until);
        $this->assertFalse((bool) $user->login_locked_permanently);
    }

    public function test_admin_unlock_resets_lockout_and_sends_email(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $user = User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        $user->forceFill([
            'login_locked_permanently' => true,
            'failed_login_attempts' => 3,
            'login_lockout_rounds' => 2,
        ])->save();

        app(LoginLockoutService::class)->unlock($user->fresh());

        $user->refresh();
        $this->assertFalse((bool) $user->login_locked_permanently);
        $this->assertSame(0, (int) $user->failed_login_attempts);
        $this->assertSame(0, (int) $user->login_lockout_rounds);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $user,
            \App\Notifications\AccountUnlockedNotification::class
        );
    }

    public function test_login_skips_forbidden_intended_url_instead_of_403(): void
    {
        $this->seed([
            \Database\Seeders\LocationSeeder::class,
            \Database\Seeders\BusinessSectorSeeder::class,
            \Database\Seeders\DummyDataSeeder::class,
        ]);

        $this->get(route('reports.by-region.index'))
            ->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => 'applicant2@wdf.go.tz',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk();
    }
}
