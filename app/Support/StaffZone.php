<?php

namespace App\Support;

use App\Models\Council;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Validation\Validator;

class StaffZone
{
    /** Roles that use a geographic zone (ward / council / region). */
    public const GEO_ROLES = [
        'cdo_ward',
        'cdo_council',
        'cdo_region',
    ];

    /** Most-specific first — drives cascade depth when multiple are checked. */
    public const GEO_ROLE_PRIORITY = [
        'cdo_ward' => 'ward',
        'cdo_council' => 'council',
        'cdo_region' => 'region',
    ];

    /** National / ministry-scope roles — no geo zone; shown as Ministry Level. */
    public const MINISTRY_LEVEL_ROLES = [
        'cdo_ministry',
        'assistant_director',
        'director',
        'chief',
        'accountant',
        'admin',
        'super_admin',
        'tehama_staff',
    ];

    public const KM_ROLE = 'km';

    /**
     * @param  iterable<int, string>  $roleNames
     */
    public static function primaryGeoRole(iterable $roleNames): ?string
    {
        $roles = collect($roleNames)->filter()->values();

        foreach (array_keys(self::GEO_ROLE_PRIORITY) as $role) {
            if ($roles->contains($role)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @param  iterable<int, string>  $roleNames
     */
    public static function expectedZoneType(iterable $roleNames): ?string
    {
        $role = self::primaryGeoRole($roleNames);

        return $role ? self::GEO_ROLE_PRIORITY[$role] : null;
    }

    /**
     * @param  iterable<int, string>  $roleNames
     */
    public static function emptyZoneTypeLabel(iterable $roleNames): string
    {
        $roles = collect($roleNames)->filter()->values();

        if ($roles->isEmpty()) {
            return __('admin.zone_none');
        }

        $hasGeo = $roles->intersect(self::GEO_ROLES)->isNotEmpty();

        if ($hasGeo) {
            return __('admin.zone_none');
        }

        if ($roles->contains(self::KM_ROLE)) {
            return __('admin.zone_ministry_permanent_secretary');
        }

        if ($roles->intersect(self::MINISTRY_LEVEL_ROLES)->isNotEmpty()) {
            return __('admin.zone_ministry_level');
        }

        return __('admin.zone_none');
    }

    public static function typeLabelForUser(User $user): string
    {
        return match ($user->zoneable_type) {
            Region::class => __('admin.zone_region'),
            Council::class => __('admin.zone_council'),
            Ward::class => __('admin.zone_ward'),
            default => self::emptyZoneTypeLabel($user->roles->pluck('name')),
        };
    }

    /**
     * Parent cascade IDs for editing an existing zone assignment.
     *
     * @return array{region_id: ?int, district_id: ?int, council_id: ?int, ward_id: ?int, zone_type: string, zone_id: ?int}
     */
    public static function cascadeIdsForUser(?User $user): array
    {
        $empty = [
            'region_id' => null,
            'district_id' => null,
            'council_id' => null,
            'ward_id' => null,
            'zone_type' => '',
            'zone_id' => null,
        ];

        if (! $user?->zoneable_type || ! $user->zoneable_id) {
            return $empty;
        }

        $zoneable = $user->relationLoaded('zoneable')
            ? $user->zoneable
            : $user->zoneable()->first();

        if (! $zoneable) {
            return $empty;
        }

        if ($zoneable instanceof Region) {
            return [
                'region_id' => $zoneable->id,
                'district_id' => null,
                'council_id' => null,
                'ward_id' => null,
                'zone_type' => 'region',
                'zone_id' => $zoneable->id,
            ];
        }

        if ($zoneable instanceof Council) {
            $zoneable->loadMissing('district.region');

            return [
                'region_id' => $zoneable->district?->region_id,
                'district_id' => $zoneable->district_id,
                'council_id' => $zoneable->id,
                'ward_id' => null,
                'zone_type' => 'council',
                'zone_id' => $zoneable->id,
            ];
        }

        if ($zoneable instanceof Ward) {
            $zoneable->loadMissing('council.district.region');

            return [
                'region_id' => $zoneable->council?->district?->region_id,
                'district_id' => $zoneable->council?->district_id ?? $zoneable->district_id,
                'council_id' => $zoneable->council_id,
                'ward_id' => $zoneable->id,
                'zone_type' => 'ward',
                'zone_id' => $zoneable->id,
            ];
        }

        return $empty;
    }

    /**
     * Boot payload for the Alpine user geo cascade form.
     *
     * @param  list<string>  $selectedRoles
     * @param  iterable<int, \App\Models\Region>|null  $regions
     * @return array<string, mixed>
     */
    public static function formCascadeBoot(?User $user, array $selectedRoles = [], ?iterable $regions = null): array
    {
        $cascade = self::cascadeIdsForUser($user);

        $regionId = old('cascade_region_id', old('zone_type') === 'region' ? old('zone_id') : $cascade['region_id']);
        $districtId = old('cascade_district_id', $cascade['district_id']);
        $councilId = old('cascade_council_id', old('zone_type') === 'council' ? old('zone_id') : $cascade['council_id']);
        $wardId = old('cascade_ward_id', old('zone_type') === 'ward' ? old('zone_id') : $cascade['ward_id']);

        $zoneType = old('zone_type', $cascade['zone_type'] ?: self::expectedZoneType($selectedRoles));
        $zoneId = old('zone_id', $cascade['zone_id']);

        return [
            'selectedRoles' => array_values($selectedRoles),
            'geoRoles' => self::GEO_ROLES,
            'roleZoneMap' => self::GEO_ROLE_PRIORITY,
            'selectedRegion' => $regionId ? (string) $regionId : '',
            'selectedDistrict' => $districtId ? (string) $districtId : '',
            'selectedCouncil' => $councilId ? (string) $councilId : '',
            'selectedWard' => $wardId ? (string) $wardId : '',
            'zoneType' => $zoneType ?: '',
            'zoneId' => $zoneId ? (string) $zoneId : '',
            'regions' => collect($regions ?? [])->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
            ])->values()->all(),
            'geoApi' => [
                'districts' => url('/api/loans/districts'),
                'councils' => url('/api/loans/councils'),
                'wards' => url('/api/loans/wards'),
            ],
            'labels' => [
                'select_region' => __('admin.select_region'),
                'select_district' => __('geo.select_district'),
                'select_council' => __('admin.select_council'),
                'select_ward' => __('admin.select_ward'),
                'geo_hint' => __('admin.geo_zone_role_hint'),
            ],
        ];
    }

    /**
     * Labels for the create/edit form (legacy Alpine empty-label helpers).
     *
     * @return array{none: string, ministry_level: string, permanent_secretary: string, geo_roles: list<string>, ministry_roles: list<string>, km_role: string}
     */
    public static function formLabels(): array
    {
        return [
            'none' => __('admin.zone_none'),
            'ministry_level' => __('admin.zone_ministry_level'),
            'permanent_secretary' => __('admin.zone_ministry_permanent_secretary'),
            'geo_roles' => self::GEO_ROLES,
            'ministry_roles' => self::MINISTRY_LEVEL_ROLES,
            'km_role' => self::KM_ROLE,
        ];
    }

    public static function validateRoleZone(Validator $validator, ?array $roles, mixed $zoneType, mixed $zoneId): void
    {
        $roles = $roles ?? [];
        $expected = self::expectedZoneType($roles);

        if ($expected === null) {
            return;
        }

        if (! $zoneType || ! $zoneId) {
            $validator->errors()->add('zone_id', __('admin.geo_zone_required'));

            return;
        }

        if ($zoneType !== $expected) {
            $validator->errors()->add('zone_type', __('admin.geo_zone_role_mismatch'));

            return;
        }

        $table = match ($expected) {
            'region' => 'regions',
            'council' => 'councils',
            'ward' => 'wards',
            default => null,
        };

        if ($table && ! \Illuminate\Support\Facades\DB::table($table)->where('id', $zoneId)->exists()) {
            $validator->errors()->add('zone_id', __('validation.exists', ['attribute' => __('admin.zone_name')]));
        }
    }
}
