<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\AccessibleHome;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessibleHomeRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
        PermissionCatalog::syncToDatabase();
    }

    public function test_login_lands_on_ticked_permission_page_not_dashboard(): void
    {
        $user = $this->makeCustomRoleUser(['view by region reports']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('reports.by-region.index'));

        $this->get(route('reports.by-region.index'))->assertOk();
        $this->get(route('dashboard'))->assertForbidden();
    }

    public function test_home_redirects_to_accessible_page_for_custom_role(): void
    {
        $user = $this->makeCustomRoleUser(['view by region reports']);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('reports.by-region.index'));
    }

    public function test_password_change_redirects_to_accessible_page(): void
    {
        $user = $this->makeCustomRoleUser(['manage users'], [
            'must_change_password' => true,
            'temporary_password_expires_at' => null,
            'password' => $this->strongPassword(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => $this->strongPassword(),
        ])->assertRedirect(route('profile.password.required'));

        $ownPassword = 'OwnPass456!';

        $this->put(route('profile.password.required.update'), [
            'password' => $ownPassword,
            'password_confirmation' => $ownPassword,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check($ownPassword, $user->fresh()->password));
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('dashboard'))->assertForbidden();
    }

    public function test_accessible_home_prefers_dashboard_when_ticked(): void
    {
        $user = $this->makeCustomRoleUser(['view dashboard', 'view by region reports']);

        $this->assertSame(route('dashboard'), AccessibleHome::url($user));
    }

    public function test_accessible_home_falls_back_to_profile_when_no_permissions(): void
    {
        $user = User::factory()->create([
            'email' => 'no.perms@wdf.go.tz',
            'is_active' => true,
        ]);

        $this->assertSame(route('profile.password.edit'), AccessibleHome::url($user));
    }

    /**
     * @param  list<string>  $permissions
     * @param  array<string, mixed>  $attributes
     */
    private function makeCustomRoleUser(array $permissions, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => 'custom_landing_role', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $user = User::factory()->create(array_merge([
            'email' => 'custom.landing@wdf.go.tz',
            'password' => 'password',
            'is_active' => true,
            'must_change_password' => false,
        ], $attributes));
        $user->syncRoles([$role]);

        return $user;
    }
}
