<?php

namespace App\Support;

use App\Models\Council;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;

class StaffZone
{
    /** Roles that use a geographic zone (ward / council / region). */
    public const GEO_ROLES = [
        'cdo_ward',
        'cdo_council',
        'cdo_region',
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
     * Labels for the create/edit form (Alpine).
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
}
