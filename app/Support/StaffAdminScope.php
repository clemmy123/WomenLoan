<?php

namespace App\Support;

use App\Models\Council;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Validator;

class StaffAdminScope
{
    /** Geo-scoped administration roles (region / council only). */
    public const SCOPED_ADMIN_ROLES = [
        'tehama_region',
        'tehama_council',
    ];

    /** National / ministry roles that scoped Tehama admins must not manage. */
    public const PROTECTED_TARGET_ROLES = [
        'super_admin',
        'admin',
        'ministry_admin',
        'applicant',
        'cdo_ministry',
        'assistant_director',
        'director',
        'km',
        'chief',
        'accountant',
    ];

    /** @var array<string, list<string>> */
    public const ASSIGNABLE_ROLES = [
        'tehama_region' => [
            'cdo_ward',
            'cdo_council',
            'cdo_region',
            'tehama_council',
            'tehama_region',
        ],
        'tehama_council' => [
            'cdo_ward',
            'cdo_council',
            'tehama_council',
        ],
    ];

    public static function isScopedAdmin(?User $user): bool
    {
        return (bool) $user?->hasAnyRole(self::SCOPED_ADMIN_ROLES);
    }

    public static function primaryScopedAdminRole(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (['tehama_council', 'tehama_region'] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @return list<string>|null null = no role restriction beyond existing sanitizeRules
     */
    public static function assignableRoleNames(?User $actor = null): ?array
    {
        $actor ??= auth()->user();
        $role = self::primaryScopedAdminRole($actor);

        if ($role === null) {
            return null;
        }

        return self::ASSIGNABLE_ROLES[$role] ?? [];
    }

    public static function applyToStaffQuery(Builder $query, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        if (! self::isScopedAdmin($actor)) {
            return;
        }

        $actor->loadMissing('zoneable');

        $query->whereDoesntHave(
            'roles',
            fn (Builder $roles) => $roles->whereIn('name', self::PROTECTED_TARGET_ROLES)
        );

        if ($actor->hasRole('tehama_region') && $actor->zoneable instanceof Region) {
            $regionId = (int) $actor->zoneable_id;

            $query->where(function (Builder $outer) use ($regionId) {
                $outer->where(function (Builder $q) use ($regionId) {
                    $q->where('zoneable_type', Region::class)
                        ->where('zoneable_id', $regionId);
                })->orWhere(function (Builder $q) use ($regionId) {
                    $q->where('zoneable_type', Council::class)
                        ->whereIn(
                            'zoneable_id',
                            Council::query()
                                ->whereHas('district', fn (Builder $d) => $d->where('region_id', $regionId))
                                ->select('id')
                        );
                })->orWhere(function (Builder $q) use ($regionId) {
                    $q->where('zoneable_type', Ward::class)
                        ->whereIn(
                            'zoneable_id',
                            Ward::query()
                                ->whereHas(
                                    'council.district',
                                    fn (Builder $d) => $d->where('region_id', $regionId)
                                )
                                ->select('id')
                        );
                });
            });

            return;
        }

        if ($actor->hasRole('tehama_council') && $actor->zoneable instanceof Council) {
            $councilId = (int) $actor->zoneable_id;

            $query->where(function (Builder $outer) use ($councilId) {
                $outer->where(function (Builder $q) use ($councilId) {
                    $q->where('zoneable_type', Council::class)
                        ->where('zoneable_id', $councilId);
                })->orWhere(function (Builder $q) use ($councilId) {
                    $q->where('zoneable_type', Ward::class)
                        ->whereIn(
                            'zoneable_id',
                            Ward::query()->where('council_id', $councilId)->select('id')
                        );
                });
            });

            return;
        }

        $query->whereRaw('0 = 1');
    }

    /**
     * Optional list filters: narrow staff by zone assignment (most specific level wins).
     */
    public static function applyListGeoFilter(
        Builder $query,
        ?int $regionId = null,
        ?int $districtId = null,
        ?int $councilId = null,
        ?int $wardId = null,
    ): void {
        if ($wardId) {
            $query->where('zoneable_type', Ward::class)
                ->where('zoneable_id', $wardId);

            return;
        }

        if ($councilId) {
            $query->where(function (Builder $outer) use ($councilId) {
                $outer->where(function (Builder $q) use ($councilId) {
                    $q->where('zoneable_type', Council::class)
                        ->where('zoneable_id', $councilId);
                })->orWhere(function (Builder $q) use ($councilId) {
                    $q->where('zoneable_type', Ward::class)
                        ->whereIn(
                            'zoneable_id',
                            Ward::query()->where('council_id', $councilId)->select('id')
                        );
                });
            });

            return;
        }

        if ($districtId) {
            $query->where(function (Builder $outer) use ($districtId) {
                $outer->where(function (Builder $q) use ($districtId) {
                    $q->where('zoneable_type', Council::class)
                        ->whereIn(
                            'zoneable_id',
                            Council::query()->where('district_id', $districtId)->select('id')
                        );
                })->orWhere(function (Builder $q) use ($districtId) {
                    $q->where('zoneable_type', Ward::class)
                        ->whereIn(
                            'zoneable_id',
                            Ward::query()
                                ->where(function (Builder $ward) use ($districtId) {
                                    $ward->where('district_id', $districtId)
                                        ->orWhereHas(
                                            'council',
                                            fn (Builder $c) => $c->where('district_id', $districtId)
                                        );
                                })
                                ->select('id')
                        );
                });
            });

            return;
        }

        if ($regionId) {
            $query->where(function (Builder $outer) use ($regionId) {
                $outer->where(function (Builder $q) use ($regionId) {
                    $q->where('zoneable_type', Region::class)
                        ->where('zoneable_id', $regionId);
                })->orWhere(function (Builder $q) use ($regionId) {
                    $q->where('zoneable_type', Council::class)
                        ->whereIn(
                            'zoneable_id',
                            Council::query()
                                ->whereHas('district', fn (Builder $d) => $d->where('region_id', $regionId))
                                ->select('id')
                        );
                })->orWhere(function (Builder $q) use ($regionId) {
                    $q->where('zoneable_type', Ward::class)
                        ->whereIn(
                            'zoneable_id',
                            Ward::query()
                                ->whereHas(
                                    'council.district',
                                    fn (Builder $d) => $d->where('region_id', $regionId)
                                )
                                ->select('id')
                        );
                });
            });
        }
    }

    public static function canManage(?User $actor, User $target): bool
    {
        $actor ??= auth()->user();

        if (! $actor || ! $actor->can('manage users')) {
            return false;
        }

        if (! self::isScopedAdmin($actor)) {
            return true;
        }

        if ($target->hasAnyRole(self::PROTECTED_TARGET_ROLES)) {
            return false;
        }

        return User::query()
            ->whereKey($target->id)
            ->tap(fn (Builder $q) => self::applyToStaffQuery($q, $actor))
            ->exists();
    }

    public static function zoneIsWithinScope(?User $actor, ?string $zoneType, mixed $zoneId): bool
    {
        $actor ??= auth()->user();

        if (! self::isScopedAdmin($actor)) {
            return true;
        }

        if (! $zoneType || ! $zoneId) {
            return false;
        }

        $actor->loadMissing('zoneable');
        $zoneId = (int) $zoneId;

        if ($actor->hasRole('tehama_region') && $actor->zoneable instanceof Region) {
            $regionId = (int) $actor->zoneable_id;

            return match ($zoneType) {
                'region' => $zoneId === $regionId,
                'council' => Council::query()
                    ->whereKey($zoneId)
                    ->whereHas('district', fn (Builder $d) => $d->where('region_id', $regionId))
                    ->exists(),
                'ward' => Ward::query()
                    ->whereKey($zoneId)
                    ->whereHas('council.district', fn (Builder $d) => $d->where('region_id', $regionId))
                    ->exists(),
                default => false,
            };
        }

        if ($actor->hasRole('tehama_council') && $actor->zoneable instanceof Council) {
            $councilId = (int) $actor->zoneable_id;

            return match ($zoneType) {
                'council' => $zoneId === $councilId,
                'ward' => Ward::query()
                    ->whereKey($zoneId)
                    ->where('council_id', $councilId)
                    ->exists(),
                default => false,
            };
        }

        return false;
    }

    public static function validateAssignableRoles(Validator $validator, ?User $actor, ?array $roles): void
    {
        $assignable = self::assignableRoleNames($actor);

        if ($assignable === null) {
            return;
        }

        foreach ($roles ?? [] as $role) {
            if (! in_array($role, $assignable, true)) {
                $validator->errors()->add('roles', __('admin.role_outside_admin_scope'));
                break;
            }
        }
    }

    public static function validateZoneWithinScope(
        Validator $validator,
        ?User $actor,
        ?array $roles,
        ?string $zoneType,
        mixed $zoneId
    ): void {
        if (! self::isScopedAdmin($actor)) {
            return;
        }

        if (StaffZone::expectedZoneType($roles ?? []) === null) {
            return;
        }

        if (! self::zoneIsWithinScope($actor, $zoneType, $zoneId)) {
            $validator->errors()->add('zone_id', __('admin.zone_outside_admin_scope'));
        }
    }
}
