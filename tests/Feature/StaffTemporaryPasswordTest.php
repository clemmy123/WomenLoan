<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffTemporaryPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_created_staff_must_change_temporary_password(): void
    {
        $this->actingAsRole('admin@wdf.go.tz')
            ->post(route('admin.users.store'), [
                'check_number' => '1234567890',
                'first_name' => 'Neema',
                'last_name' => 'Staff',
                'email' => 'neema.staff@wdf.go.tz',
                'phone' => '0712345111',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['accountant'],
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'neema.staff@wdf.go.tz')->firstOrFail();

        $this->assertTrue($user->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);
    }

    public function test_first_login_starts_two_minute_window_and_forces_password_change(): void
    {
        $user = $this->makeStaffWithTemporaryPassword();

        $this->post('/login', [
            'email' => $user->email,
            'password' => $this->strongPassword(),
        ])->assertRedirect(route('profile.password.required'));

        $user->refresh();

        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->temporary_password_expires_at);
        $this->assertTrue(
            $user->temporary_password_expires_at->lessThanOrEqualTo(
                now()->addMinutes((int) config('wdf.temporary_password_minutes', 2))->addSecond()
            )
        );

        $this->get(route('dashboard'))->assertRedirect(route('profile.password.required'));
    }

    public function test_staff_can_set_own_password_within_window(): void
    {
        $user = $this->makeStaffWithTemporaryPassword();

        $this->post('/login', [
            'email' => $user->email,
            'password' => $this->strongPassword(),
        ]);

        $ownPassword = 'OwnPass456!';

        $this->put(route('profile.password.required.update'), [
            'password' => $ownPassword,
            'password_confirmation' => $ownPassword,
        ])->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);
        $this->assertTrue(Hash::check($ownPassword, $user->password));

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_expired_temporary_password_blocks_login(): void
    {
        $user = $this->makeStaffWithTemporaryPassword();
        $user->forceFill([
            'temporary_password_expires_at' => now()->subMinute(),
        ])->save();

        $this->from(route('login'))
            ->post('/login', [
                'email' => $user->email,
                'password' => $this->strongPassword(),
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_applicant_login_is_not_forced_to_change_password(): void
    {
        $this->post('/login', [
            'email' => 'applicant2@wdf.go.tz',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();
        $this->assertFalse($user->must_change_password);
    }

    private function makeStaffWithTemporaryPassword(): User
    {
        $user = User::factory()->create([
            'email' => 'temp.staff@wdf.go.tz',
            'password' => $this->strongPassword(),
            'is_active' => true,
            'must_change_password' => true,
            'temporary_password_expires_at' => null,
        ]);
        $user->assignRole('accountant');

        return $user;
    }
}
