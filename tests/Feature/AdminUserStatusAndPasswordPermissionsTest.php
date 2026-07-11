<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserStatusAndPasswordPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_admin_can_reset_password_and_deactivate_user(): void
    {
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $this->actingAsRole('admin@wdf.go.tz')
            ->put(route('admin.users.update', $target), $this->validUpdatePayload($target, [
                'password' => 'NewTempPass1!',
                'password_confirmation' => 'NewTempPass1!',
                'is_active' => '0',
            ]))
            ->assertRedirect(route('admin.users.index'));

        $target->refresh();

        $this->assertFalse($target->is_active);
        $this->assertTrue($target->must_change_password);
        $this->assertTrue(Hash::check('NewTempPass1!', $target->password));
    }

    public function test_user_without_reset_permission_cannot_change_password(): void
    {
        $actor = $this->makeUserManagerWithout([
            'reset user password',
        ]);
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();
        $originalHash = $target->password;

        $this->actingAs($actor)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), $this->validUpdatePayload($target, [
                'password' => 'NewTempPass1!',
                'password_confirmation' => 'NewTempPass1!',
            ]))
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors('password');

        $this->assertSame($originalHash, $target->fresh()->password);
    }

    public function test_user_without_deactivate_permission_cannot_deactivate(): void
    {
        $actor = $this->makeUserManagerWithout([
            'deactivate users',
        ]);
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $this->actingAs($actor)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), $this->validUpdatePayload($target, [
                'is_active' => '0',
            ]))
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_user_without_activate_permission_cannot_activate(): void
    {
        $actor = $this->makeUserManagerWithout([
            'activate users',
        ]);
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();
        $target->forceFill(['is_active' => false])->save();

        $this->actingAs($actor)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), $this->validUpdatePayload($target, [
                'is_active' => '1',
            ]))
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors('is_active');

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_new_permissions_appear_on_roles_edit_tick_boxes(): void
    {
        $role = Role::where('name', 'chief')->firstOrFail();

        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.roles.edit', $role));

        $response->assertOk();
        $response->assertSee('value="reset user password"', false);
        $response->assertSee('value="activate users"', false);
        $response->assertSee('value="deactivate users"', false);
        $response->assertSee(e(permission_label('reset user password')), false);
        $response->assertSee(e(permission_label('activate users')), false);
        $response->assertSee(e(permission_label('deactivate users')), false);
    }

    /** @param  list<string>  $without */
    private function makeUserManagerWithout(array $without): User
    {
        $permissions = [
            'manage users',
            'reset user password',
            'activate users',
            'deactivate users',
        ];

        $user = User::factory()->create([
            'email' => 'usermanager@wdf.go.tz',
            'is_active' => true,
        ]);
        $user->givePermissionTo(array_values(array_diff($permissions, $without)));

        return $user;
    }

    /** @param  array<string, mixed>  $overrides */
    private function validUpdatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'check_number' => $user->check_number ?: '1234567890',
            'first_name' => $user->first_name ?: 'Test',
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name ?: 'User',
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => $user->roles->pluck('name')->all(),
            'is_active' => $user->is_active ? '1' : '0',
        ], $overrides);
    }
}
