<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AdminDashboardService;
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
            ->assertSee(__('admin.dashboard_users_by_role'), false)
            ->assertSee('adminRolesChart', false)
            ->assertSee('adminAuditChart', false)
            ->assertSee('admin-dashboard-chart-data', false);
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

        $role = Role::where('name', 'chief')->firstOrFail();

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('value="view administration dashboard"', false)
            ->assertSee(__('permissions.view administration dashboard'), false);
    }

    public function test_admin_overview_user_stats_exclude_applicants(): void
    {
        $expectedActiveStaff = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', '!=', 'applicant'))
            ->count();

        $applicantCount = User::query()
            ->role('applicant')
            ->count();

        $this->assertGreaterThan(0, $applicantCount);
        $this->assertGreaterThan(0, $expectedActiveStaff);

        $summary = app(AdminDashboardService::class)->summary();
        $roles = app(AdminDashboardService::class)->usersByRole();

        $this->assertSame($expectedActiveStaff, $summary['total_users']);
        $this->assertSame($expectedActiveStaff, $summary['active_users']);
        $this->assertSame(
            Role::query()->where('name', '!=', 'applicant')->count(),
            $summary['roles_count']
        );
        $this->assertFalse($roles->contains(fn (array $row) => $row['role'] === 'applicant'));
        $this->assertSame(
            $roles->sum('count'),
            $roles->reject(fn (array $row) => $row['role'] === 'applicant')->sum('count')
        );

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="adminRolesChart"', false)
            ->assertSee((string) $expectedActiveStaff, false);
    }

    public function test_deactivated_staff_are_excluded_from_active_stats_until_reactivated(): void
    {
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();
        $before = app(AdminDashboardService::class)->summary();

        $target->update(['is_active' => false]);

        $afterDeactivate = app(AdminDashboardService::class)->summary();
        $this->assertSame($before['active_users'] - 1, $afterDeactivate['active_users']);
        $this->assertSame($before['total_users'] - 1, $afterDeactivate['total_users']);
        $this->assertSame($before['inactive_users'] + 1, $afterDeactivate['inactive_users']);

        $accountantRoleCount = app(AdminDashboardService::class)
            ->usersByRole()
            ->firstWhere('role', 'accountant')['count'] ?? 0;

        $this->assertSame(
            User::query()
                ->where('is_active', true)
                ->role('accountant')
                ->count(),
            $accountantRoleCount
        );

        $target->update(['is_active' => true]);

        $afterReactivate = app(AdminDashboardService::class)->summary();
        $this->assertSame($before['active_users'], $afterReactivate['active_users']);
        $this->assertSame($before['inactive_users'], $afterReactivate['inactive_users']);
    }

    public function test_admin_dashboard_audit_series_has_seven_days(): void
    {
        $series = app(AdminDashboardService::class)->auditActivitySeries(7);

        $this->assertCount(7, $series['labels']);
        $this->assertCount(7, $series['data']);
    }

    public function test_admin_users_index_excludes_applicants(): void
    {
        $applicant = User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();
        $staff = User::where('email', 'ward.cdo@wdf.go.tz')->firstOrFail();

        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee($staff->email, false);
        $response->assertDontSee($applicant->email, false);
        $response->assertDontSee('value="applicant"', false);
    }
}
