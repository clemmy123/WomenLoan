<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_admin_can_create_staff_user_with_required_identity_fields(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->post(route('admin.users.store'), [
                'check_number' => '1234567890',
                'first_name' => 'Asha',
                'middle_name' => 'Juma',
                'last_name' => 'Mushi',
                'email' => 'asha.mushi@wdf.go.tz',
                'phone' => '0712345999',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['accountant'],
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'asha.mushi@wdf.go.tz')->firstOrFail();

        $this->assertSame('1234567890', $user->check_number);
        $this->assertSame('Asha', $user->first_name);
        $this->assertSame('Juma', $user->middle_name);
        $this->assertSame('Mushi', $user->last_name);
        $this->assertSame('Asha Juma Mushi', $user->name);
        $this->assertSame('255712345999', $user->phone);
        $this->assertTrue($user->hasRole('accountant'));
    }

    public function test_staff_user_create_requires_check_number_and_strong_password(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'first_name' => 'Asha',
                'last_name' => 'Mushi',
                'email' => 'asha2@wdf.go.tz',
                'phone' => '0712345888',
                'password' => 'weak',
                'password_confirmation' => 'weak',
                'roles' => ['accountant'],
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['check_number', 'password']);
    }

    public function test_check_number_must_be_digits_only_max_ten(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'check_number' => 'ABC1234567',
                'first_name' => 'Asha',
                'last_name' => 'Mushi',
                'email' => 'asha3@wdf.go.tz',
                'phone' => '0712345777',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['accountant'],
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'asha3@wdf.go.tz',
            'check_number' => '1234567',
        ]);

        $this->actingAsRole('admin@wdf.go.tz')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'check_number' => '12345678901',
                'first_name' => 'Asha',
                'last_name' => 'Mushi',
                'email' => 'asha4@wdf.go.tz',
                'phone' => '0712345666',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['accountant'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'asha4@wdf.go.tz',
            'check_number' => '1234567890',
        ]);
    }

    public function test_create_form_shows_check_number_and_password_requirements(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee(__('admin.check_number'), false);
        $response->assertSee(__('applicants.first_name'), false);
        $response->assertSee(__('applicants.middle_name'), false);
        $response->assertSee(__('applicants.last_name'), false);
        $response->assertSee(__('auth.password_requirements_title'), false);
        $response->assertSee('data-phone-field', false);
    }
}
