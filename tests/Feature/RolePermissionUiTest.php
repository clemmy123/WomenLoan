<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RolePermissionUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_roles_edit_ui_lists_each_catalog_permission_individually(): void
    {
        $role = Role::where('name', 'chief')->firstOrFail();
        $catalog = PermissionCatalog::allPermissionNames();

        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.roles.edit', $role));

        $response->assertOk();
        $response->assertSee(__('admin.permissions_tick_hint'), false);

        foreach ($catalog as $permission) {
            $response->assertSee('value="'.$permission.'"', false);
            $response->assertSee(e(permission_label($permission)), false);
        }

        $response->assertSee(__('nav.assign_accountant_queue'), false);
        $response->assertSee(__('nav.my_disbursements'), false);
    }

    public function test_permission_catalog_sync_creates_missing_permissions_one_by_one(): void
    {
        Permission::where('name', 'assign accountant')->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->assertDatabaseMissing('permissions', ['name' => 'assign accountant']);

        PermissionCatalog::syncToDatabase();

        $this->assertDatabaseHas('permissions', [
            'name' => 'assign accountant',
            'guard_name' => 'web',
        ]);

        foreach (PermissionCatalog::allPermissionNames() as $name) {
            $this->assertDatabaseHas('permissions', [
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_chief_and_accountant_default_permissions_match_scoped_queues(): void
    {
        $chief = Role::where('name', 'chief')->firstOrFail();
        $accountant = Role::where('name', 'accountant')->firstOrFail();

        $this->assertTrue($chief->hasPermissionTo('assign accountant'));
        $this->assertFalse($chief->hasPermissionTo('view all loans'));

        $this->assertTrue($accountant->hasPermissionTo('disburse loan'));
        $this->assertFalse($accountant->hasPermissionTo('view all loans'));
        $this->assertFalse($accountant->hasPermissionTo('record repayment'));

        $applicant = Role::where('name', 'applicant')->firstOrFail();
        $this->assertTrue($applicant->hasPermissionTo('record repayment'));
    }
}
