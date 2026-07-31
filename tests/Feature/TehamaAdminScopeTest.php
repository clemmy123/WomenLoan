<?php

namespace Tests\Feature;

use App\Models\Council;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TehamaAdminScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_admin_can_filter_users_by_region_and_role(): void
    {
        $region = \App\Models\Region::where('name', 'Dodoma')->firstOrFail();

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.index', [
                'region_id' => $region->id,
                'role' => 'cdo_ward',
            ]))
            ->assertOk()
            ->assertSee('ward.cdo@wdf.go.tz', false)
            ->assertDontSee('accountant1@wdf.go.tz', false);
    }

    public function test_region_tehama_geo_filter_stays_within_scope(): void
    {
        $otherRegion = \App\Models\Region::query()->where('name', '!=', 'Dodoma')->firstOrFail();

        $response = $this->actingAsRole('region.tehama@wdf.go.tz')
            ->get(route('admin.users.index', [
                'region_id' => $otherRegion->id,
                'role' => 'cdo_ward',
            ]));

        $response->assertOk();
        $response->assertSee('ward.cdo@wdf.go.tz', false);
        // Outside-region request is clamped back to Dodoma scope.
        $response->assertDontSee('accountant1@wdf.go.tz', false);
    }

    public function test_region_tehama_users_by_role_chart_is_scoped_not_system_wide(): void
    {
        $this->actingAsRole('region.tehama@wdf.go.tz');

        $rows = app(\App\Services\AdminDashboardService::class)->usersByRole();
        $byRole = $rows->keyBy('role');

        // System has national accountants; region ICT must not see that system-wide role slice.
        $this->assertFalse($byRole->has('accountant'));
        $this->assertFalse($byRole->has('admin'));

        $cdoWardCount = (int) ($byRole->get('cdo_ward')['count'] ?? 0);
        $this->assertSame(
            User::query()
                ->where('is_active', true)
                ->role('cdo_ward')
                ->where('zoneable_type', Ward::class)
                ->whereIn(
                    'zoneable_id',
                    Ward::query()
                        ->whereHas('council.district', fn ($d) => $d->where('region_id', Region::where('name', 'Dodoma')->value('id')))
                        ->select('id')
                )
                ->count(),
            $cdoWardCount
        );

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard_users_by_role_scoped'), false)
            ->assertSee(__('admin.dashboard_users_by_role_scoped_help', ['zone' => 'Dodoma']), false);
    }

    public function test_council_tehama_users_by_role_chart_is_scoped_not_system_wide(): void
    {
        $this->actingAsRole('council.tehama@wdf.go.tz');

        $rows = app(\App\Services\AdminDashboardService::class)->usersByRole();
        $byRole = $rows->keyBy('role');

        $this->assertFalse($byRole->has('accountant'));
        $this->assertFalse($byRole->has('cdo_region'));

        $council = Council::where('name', 'Dodoma City Council')->firstOrFail();

        $this->assertSame(
            User::query()
                ->where('is_active', true)
                ->role('cdo_council')
                ->where('zoneable_type', Council::class)
                ->where('zoneable_id', $council->id)
                ->count(),
            (int) ($byRole->get('cdo_council')['count'] ?? 0)
        );

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard_users_by_role_scoped'), false)
            ->assertSee($council->name, false);
    }

    public function test_ministry_admin_sees_national_staff_without_geo_scope(): void
    {
        $response = $this->actingAsRole('ministry.admin@wdf.go.tz')
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('accountant1@wdf.go.tz', false);
        $response->assertSee('ward.cdo@wdf.go.tz', false);
        $response->assertSee('ministry@wdf.go.tz', false);
    }

    public function test_region_tehama_sees_only_staff_in_own_region(): void
    {
        $response = $this->actingAsRole('region.tehama@wdf.go.tz')
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('ward.cdo@wdf.go.tz', false);
        $response->assertSee('council.cdo@wdf.go.tz', false);
        $response->assertSee('region.cdo@wdf.go.tz', false);
        $response->assertSee('council.tehama@wdf.go.tz', false);
        $response->assertDontSee('accountant1@wdf.go.tz', false);
        $response->assertDontSee('ministry@wdf.go.tz', false);
    }

    public function test_council_tehama_sees_only_staff_in_own_council(): void
    {
        $response = $this->actingAsRole('council.tehama@wdf.go.tz')
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('ward.cdo@wdf.go.tz', false);
        $response->assertSee('council.cdo@wdf.go.tz', false);
        $response->assertDontSee('region.cdo@wdf.go.tz', false);
        $response->assertDontSee('accountant1@wdf.go.tz', false);
    }

    public function test_region_tehama_cannot_view_national_staff(): void
    {
        $accountant = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $this->actingAsRole('region.tehama@wdf.go.tz')
            ->get(route('admin.users.show', $accountant))
            ->assertForbidden();
    }

    public function test_region_tehama_cannot_create_user_outside_region_zone(): void
    {
        $otherRegion = Region::query()->where('name', '!=', 'Dodoma')->firstOrFail();

        $this->actingAsRole('region.tehama@wdf.go.tz')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'check_number' => '2000000001',
                'first_name' => 'Outside',
                'last_name' => 'Region',
                'email' => 'outside.region@wdf.go.tz',
                'phone' => '0712345333',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['cdo_region'],
                'zone_type' => 'region',
                'zone_id' => $otherRegion->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors(['zone_id']);
    }

    public function test_region_tehama_can_create_cdo_ward_in_own_region(): void
    {
        $ward = Ward::where('name', 'Hazina Ward')->firstOrFail();

        $this->actingAsRole('region.tehama@wdf.go.tz')
            ->post(route('admin.users.store'), [
                'check_number' => '2000000002',
                'first_name' => 'Local',
                'last_name' => 'Ward',
                'email' => 'local.ward@wdf.go.tz',
                'phone' => '0712345444',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['cdo_ward'],
                'zone_type' => 'ward',
                'zone_id' => $ward->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'local.ward@wdf.go.tz')->firstOrFail();
        $this->assertTrue($user->hasRole('cdo_ward'));
        $this->assertSame(Ward::class, $user->zoneable_type);
        $this->assertSame($ward->id, $user->zoneable_id);
    }

    public function test_council_tehama_cannot_assign_national_roles(): void
    {
        $target = User::where('email', 'ward.cdo@wdf.go.tz')->firstOrFail();

        $this->actingAsRole('council.tehama@wdf.go.tz')
            ->from(route('admin.users.assign-roles', $target))
            ->put(route('admin.users.assign-roles.update', $target), [
                'roles' => ['accountant'],
            ])
            ->assertRedirect(route('admin.users.assign-roles', $target))
            ->assertSessionHasErrors(['roles']);
    }

    public function test_tehama_roles_require_geo_zone(): void
    {
        $this->actingAsRole('admin@wdf.go.tz')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'check_number' => '2000000003',
                'first_name' => 'No',
                'last_name' => 'Zone',
                'email' => 'tehama.nozone@wdf.go.tz',
                'phone' => '0712345555',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['tehama_region'],
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors(['zone_id']);

        $council = Council::where('name', 'Dodoma City Council')->firstOrFail();

        $this->actingAsRole('admin@wdf.go.tz')
            ->post(route('admin.users.store'), [
                'check_number' => '2000000004',
                'first_name' => 'Council',
                'last_name' => 'Tehama',
                'email' => 'tehama.withzone@wdf.go.tz',
                'phone' => '0712345666',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['tehama_council'],
                'zone_type' => 'council',
                'zone_id' => $council->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'tehama.withzone@wdf.go.tz')->firstOrFail();
        $this->assertTrue($user->hasRole('tehama_council'));
        $this->assertSame(Council::class, $user->zoneable_type);
    }
}
