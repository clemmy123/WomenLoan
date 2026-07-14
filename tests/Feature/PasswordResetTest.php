<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_login_page_shows_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('auth.forgot_password'));
    }

    public function test_guest_forgot_password_always_reports_user_not_existed(): void
    {
        Notification::fake();

        $user = User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        $this->get(route('password.request'))->assertOk();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => __('passwords.user')]);

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => __('passwords.user')]);

        Notification::assertNothingSent();
    }

    public function test_guest_cannot_complete_password_reset_via_token_url(): void
    {
        Notification::fake();

        $user = User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        $this->get(route('password.reset', ['token' => 'fake-token', 'email' => $user->email]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('passwords.user')]);

        $this->post(route('password.update'), [
            'token' => 'fake-token',
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect()
            ->assertSessionHasErrors(['email' => __('passwords.user')]);

        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_ward_cdo_cannot_reset_password_via_admin_url(): void
    {
        $target = User::where('email', 'council.cdo@wdf.go.tz')->firstOrFail();

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->get(route('admin.users.edit', $target))
            ->assertForbidden();

        $this->actingAsRole('ward.cdo@wdf.go.tz')
            ->put(route('admin.users.update', $target), [
                'check_number' => $target->check_number ?: '1000000003',
                'first_name' => $target->first_name ?: 'John',
                'last_name' => $target->last_name ?: 'Massawe',
                'email' => $target->email,
                'phone' => $target->phone,
                'password' => 'HackedPass1!',
                'password_confirmation' => 'HackedPass1!',
                'roles' => $target->roles->pluck('name')->all(),
                'is_active' => '1',
            ])
            ->assertForbidden();
    }
}
