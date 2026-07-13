<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_roles_ui_lists_each_report_submenu_permission(): void
    {
        $role = Role::where('name', 'chief')->firstOrFail();

        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.roles.edit', $role));

        $response->assertOk();

        foreach ([
            'view reports overview',
            'view application reports',
            'view payment reports',
            'view outstanding reports',
            'view overdue reports',
            'view by region reports',
            'view by type reports',
            'view by sector reports',
            'view by bank reports',
            'view by monthly reports',
            'view by age reports',
        ] as $permission) {
            $response->assertSee('value="'.$permission.'"', false);
            $response->assertSee(e(permission_label($permission)), false);
        }

        $response->assertDontSee('value="view reports"', false);
        $response->assertDontSee('value="view analytical reports"', false);
    }

    public function test_user_with_only_by_region_cannot_open_application_reports(): void
    {
        PermissionCatalog::syncToDatabase();

        $role = Role::firstOrCreate(['name' => 'report_region_only', 'guard_name' => 'web']);
        $role->syncPermissions(['view dashboard', 'view by region reports']);

        $user = User::factory()->create([
            'email' => 'region.only@wdf.go.tz',
            'is_active' => true,
        ]);
        $user->syncRoles([$role]);

        $this->actingAs($user)
            ->get(route('reports.by-region.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('reports.applications.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('reports.analytical.overview'))
            ->assertForbidden();
    }

    public function test_legacy_view_reports_permission_migrates_to_submenu_permissions(): void
    {
        $legacy = Permission::firstOrCreate([
            'name' => 'view reports',
            'guard_name' => 'web',
        ]);

        $role = Role::firstOrCreate(['name' => 'legacy_reports_role', 'guard_name' => 'web']);
        $role->syncPermissions([$legacy->name]);

        PermissionCatalog::syncToDatabase();

        $role->refresh();

        $this->assertTrue($role->hasPermissionTo('view reports overview'));
        $this->assertTrue($role->hasPermissionTo('view by region reports'));
        $this->assertFalse($role->hasPermissionTo('view payment reports'));
        $this->assertDatabaseMissing('permissions', ['name' => 'view reports']);
    }
}
