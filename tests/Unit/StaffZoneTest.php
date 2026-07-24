<?php

namespace Tests\Unit;

use App\Models\Region;
use App\Models\User;
use App\Support\StaffZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffZoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_ministry_roles_use_ministry_level_label_when_no_geo_zone(): void
    {
        foreach (['cdo_ministry', 'assistant_director', 'director', 'chief', 'accountant'] as $role) {
            $this->assertSame(
                __('admin.zone_ministry_level'),
                StaffZone::emptyZoneTypeLabel([$role]),
                "Expected Ministry Level for {$role}"
            );
        }
    }

    public function test_km_uses_ministry_permanent_secretary_label(): void
    {
        $this->assertSame(
            __('admin.zone_ministry_permanent_secretary'),
            StaffZone::emptyZoneTypeLabel(['km'])
        );
    }

    public function test_geo_cdo_roles_keep_none_label(): void
    {
        foreach (['cdo_ward', 'cdo_council', 'cdo_region'] as $role) {
            $this->assertSame(
                __('admin.zone_none'),
                StaffZone::emptyZoneTypeLabel([$role]),
                "Expected None for {$role}"
            );
        }
    }

    public function test_show_page_displays_ministry_level_for_accountant(): void
    {
        $user = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee(__('admin.zone_ministry_level'), false);
    }

    public function test_show_page_displays_permanent_secretary_for_km(): void
    {
        $user = User::where('email', 'km@wdf.go.tz')->firstOrFail();

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee(__('admin.zone_ministry_permanent_secretary'), false);
    }

    public function test_assigned_geo_zone_still_shows_region_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('cdo_region');
        $region = Region::query()->firstOrFail();
        $user->zoneable()->associate($region);
        $user->save();

        $this->assertSame(__('admin.zone_region'), StaffZone::typeLabelForUser($user->fresh(['roles', 'zoneable'])));
    }
}
