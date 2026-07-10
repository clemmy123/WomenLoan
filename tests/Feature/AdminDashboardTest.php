<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_admin_can_view_administration_dashboard(): void
    {
        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('nav.admin_dashboard'), false)
            ->assertSee(__('admin.dashboard_summary_title'), false)
            ->assertSee(__('admin.dashboard_users_by_role'), false);
    }

    public function test_non_admin_cannot_view_administration_dashboard(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_administration_dashboard_permission_is_in_catalog(): void
    {
        $this->assertContains(
            'view administration dashboard',
            \App\Support\PermissionCatalog::allPermissionNames()
        );

        $role = \App\Models\Role::where('name', 'chief')->firstOrFail();

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('value="view administration dashboard"', false)
            ->assertSee(__('permissions.view administration dashboard'), false);
    }
}
