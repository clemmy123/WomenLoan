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
        $expectedStaff = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', '!=', 'applicant'))
            ->count();

        $applicantCount = User::query()
            ->role('applicant')
            ->count();

        $this->assertGreaterThan(0, $applicantCount);
        $this->assertGreaterThan(0, $expectedStaff);

        $summary = app(AdminDashboardService::class)->summary();
        $roles = app(AdminDashboardService::class)->usersByRole();

        $this->assertSame($expectedStaff, $summary['total_users']);
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
            ->assertSee((string) $expectedStaff, false);
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
