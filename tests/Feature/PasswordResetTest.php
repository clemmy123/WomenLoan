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

    public function test_forgot_password_page_can_request_reset_link(): void
    {
        Notification::fake();

        $user = User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        $this->get(route('password.request'))->assertOk();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();

        $this->post(route('password.email'), ['email' => $user->email]);

        $notification = Notification::sent($user, ResetPassword::class)->first();
        $token = $notification->token;

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee(__('auth.reset_password'));

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('NewPassword123!', $user->fresh()->password)
        );
    }
}
