<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Council;
use App\Models\District;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GeoHierarchyService
{
    private const TTL = 3600;

    public function regions(): Collection
    {
        return Cache::remember('geo.regions', self::TTL, function () {
            return Region::query()->orderBy('name')->get(['id', 'name', 'code']);
        });
    }

    public function districtsFor(Region $region): Collection
    {
        return Cache::remember("geo.districts.{$region->id}", self::TTL, function () use ($region) {
            return $region->districts()->orderBy('name')->get(['id', 'name', 'code', 'region_id']);
        });
    }

    public function councilsFor(District $district): Collection
    {
        return Cache::remember("geo.councils.{$district->id}", self::TTL, function () use ($district) {
            return $district->councils()->orderBy('name')->get(['id', 'name', 'code', 'district_id']);
        });
    }

    public function wardsFor(Council $council): Collection
    {
        return Cache::remember("geo.wards.{$council->id}", self::TTL, function () use ($council) {
            return $council->wards()->orderBy('name')->get(['id', 'name', 'code', 'council_id']);
        });
    }

    public function streetsFor(Ward $ward): Collection
    {
        return Cache::remember("geo.streets.{$ward->id}", self::TTL, function () use ($ward) {
            return $ward->streets()->orderBy('name')->get(['id', 'name', 'code', 'ward_id']);
        });
    }

    public function resolveLocationChain(Applicant $applicant): array
    {
        $applicant->loadMissing('location.ward.council.district.region');

        $location = $applicant->location;
        $ward = $location?->ward;
        $council = $ward?->council;
        $district = $council?->district;
        $region = $district?->region;

        return compact('location', 'ward', 'council', 'district', 'region');
    }

    public static function apiUrls(): array
    {
        return [
            'districts' => url('/api/loans/districts'),
            'councils' => url('/api/loans/councils'),
            'wards' => url('/api/loans/wards'),
            'streets' => url('/api/loans/streets'),
        ];
    }

    public function allCouncils(): Collection
    {
        return Cache::remember('geo.all.councils', self::TTL, function () {
            return Council::query()->orderBy('name')->get(['id', 'name']);
        });
    }

    public function allWards(): Collection
    {
        return Cache::remember('geo.all.wards', self::TTL, function () {
            return Ward::query()->orderBy('name')->get(['id', 'name']);
        });
    }

    public function isGeoRestricted(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user?->hasRole(['cdo_ward', 'cdo_council', 'cdo_region']);
    }

    /**
     * @return array{
     *     empty: bool,
     *     region_id: ?int,
     *     district_id: ?int,
     *     council_id: ?int,
     *     ward_id: ?int,
     *     lock: array<string, string>
     * }|null null = unrestricted (ministry+)
     */
    public function zoneBounds(?User $user = null): ?array
    {
        $user ??= auth()->user();

        if (! $user || ! $this->isGeoRestricted($user)) {
            return null;
        }

        if (! $user->zoneable_id || ! $user->zoneable_type) {
            return [
                'empty' => true,
                'region_id' => null,
                'district_id' => null,
                'council_id' => null,
                'ward_id' => null,
                'lock' => [],
            ];
        }

        $user->loadMissing('zoneable');

        if ($user->hasRole('cdo_ward') && $user->zoneable instanceof Ward) {
            $ward = $user->zoneable;
            $ward->loadMissing('council.district.region');

            return [
                'empty' => false,
                'region_id' => $ward->council?->district?->region_id,
                'district_id' => $ward->district_id ?: $ward->council?->district_id,
                'council_id' => $ward->council_id,
                'ward_id' => $ward->id,
                'lock' => [
                    'region_id' => (string) ($ward->council?->district?->region_id ?? ''),
                    'district_id' => (string) ($ward->district_id ?: $ward->council?->district_id ?? ''),
                    'council_id' => (string) $ward->council_id,
                    'ward_id' => (string) $ward->id,
                ],
            ];
        }

        if ($user->hasRole('cdo_council') && $user->zoneable instanceof Council) {
            $council = $user->zoneable;
            $council->loadMissing('district.region');

            return [
                'empty' => false,
                'region_id' => $council->district?->region_id,
                'district_id' => $council->district_id,
                'council_id' => $council->id,
                'ward_id' => null,
                'lock' => [
                    'region_id' => (string) ($council->district?->region_id ?? ''),
                    'district_id' => (string) $council->district_id,
                    'council_id' => (string) $council->id,
                ],
            ];
        }

        if ($user->hasRole('cdo_region') && $user->zoneable instanceof Region) {
            $region = $user->zoneable;

            return [
                'empty' => false,
                'region_id' => $region->id,
                'district_id' => null,
                'council_id' => null,
                'ward_id' => null,
                'lock' => [
                    'region_id' => (string) $region->id,
                ],
            ];
        }

        return [
            'empty' => true,
            'region_id' => null,
            'district_id' => null,
            'council_id' => null,
            'ward_id' => null,
            'lock' => [],
        ];
    }

    public function regionsForUser(?User $user = null): Collection
    {
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return $this->regions();
        }

        if (($bounds['empty'] ?? false) || ! $bounds['region_id']) {
            return collect();
        }

        return Region::query()
            ->whereKey($bounds['region_id'])
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function districtsForUser(Region $region, ?User $user = null): Collection
    {
        $this->assertRegionAllowed($region->id, $user);

        $districts = $this->districtsFor($region);
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return $districts;
        }

        if ($bounds['district_id']) {
            return $districts->where('id', $bounds['district_id'])->values();
        }

        return $districts;
    }

    public function councilsForUser(District $district, ?User $user = null): Collection
    {
        $this->assertDistrictAllowed($district->id, $user);

        $councils = $this->councilsFor($district);
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return $councils;
        }

        if ($bounds['council_id']) {
            return $councils->where('id', $bounds['council_id'])->values();
        }

        return $councils;
    }

    public function wardsForUser(Council $council, ?User $user = null): Collection
    {
        $this->assertCouncilAllowed($council->id, $user);

        $wards = $this->wardsFor($council);
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return $wards;
        }

        if ($bounds['ward_id']) {
            return $wards->where('id', $bounds['ward_id'])->values();
        }

        return $wards;
    }

    public function streetsForUser(Ward $ward, ?User $user = null): Collection
    {
        $this->assertWardAllowed($ward->id, $user);

        return $this->streetsFor($ward);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function clampGeoFilters(array $filters, ?User $user = null): array
    {
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return $filters;
        }

        if ($bounds['empty'] ?? false) {
            $filters['region_id'] = '0';
            $filters['district_id'] = '0';
            $filters['council_id'] = '0';
            $filters['ward_id'] = '0';
            $filters['street_id'] = '0';

            return $filters;
        }

        foreach (['region_id', 'district_id', 'council_id', 'ward_id'] as $key) {
            if (! empty($bounds['lock'][$key])) {
                $filters[$key] = $bounds['lock'][$key];
            }
        }

        if (! empty($filters['region_id']) && (int) $filters['region_id'] !== (int) $bounds['region_id']) {
            $filters['region_id'] = $bounds['region_id'] ? (string) $bounds['region_id'] : null;
            $filters['district_id'] = $bounds['district_id'] ? (string) $bounds['district_id'] : null;
            $filters['council_id'] = $bounds['council_id'] ? (string) $bounds['council_id'] : null;
            $filters['ward_id'] = $bounds['ward_id'] ? (string) $bounds['ward_id'] : null;
            $filters['street_id'] = null;
        }

        if (! empty($filters['district_id']) && $bounds['district_id']
            && (int) $filters['district_id'] !== (int) $bounds['district_id']) {
            $filters['district_id'] = (string) $bounds['district_id'];
            $filters['council_id'] = $bounds['council_id'] ? (string) $bounds['council_id'] : null;
            $filters['ward_id'] = $bounds['ward_id'] ? (string) $bounds['ward_id'] : null;
            $filters['street_id'] = null;
        }

        if (! empty($filters['council_id']) && $bounds['council_id']
            && (int) $filters['council_id'] !== (int) $bounds['council_id']) {
            $filters['council_id'] = (string) $bounds['council_id'];
            $filters['ward_id'] = $bounds['ward_id'] ? (string) $bounds['ward_id'] : null;
            $filters['street_id'] = null;
        }

        if (! empty($filters['ward_id']) && $bounds['ward_id']
            && (int) $filters['ward_id'] !== (int) $bounds['ward_id']) {
            $filters['ward_id'] = (string) $bounds['ward_id'];
            $filters['street_id'] = null;
        }

        if (! empty($filters['district_id']) && $bounds['region_id'] && ! $bounds['district_id']) {
            $district = District::query()->find((int) $filters['district_id']);
            if (! $district || (int) $district->region_id !== (int) $bounds['region_id']) {
                $filters['district_id'] = null;
                $filters['council_id'] = null;
                $filters['ward_id'] = null;
                $filters['street_id'] = null;
            }
        }

        if (! empty($filters['council_id']) && $bounds['region_id'] && ! $bounds['council_id']) {
            $council = Council::query()->with('district')->find((int) $filters['council_id']);
            if (! $council || (int) $council->district?->region_id !== (int) $bounds['region_id']) {
                $filters['council_id'] = null;
                $filters['ward_id'] = null;
                $filters['street_id'] = null;
            }
        }

        if (! empty($filters['ward_id']) && ! $bounds['ward_id']) {
            $ward = Ward::query()->with('council.district')->find((int) $filters['ward_id']);
            $allowed = false;
            if ($ward && $bounds['council_id']) {
                $allowed = (int) $ward->council_id === (int) $bounds['council_id'];
            } elseif ($ward && $bounds['region_id']) {
                $allowed = (int) $ward->council?->district?->region_id === (int) $bounds['region_id'];
            }
            if (! $allowed) {
                $filters['ward_id'] = null;
                $filters['street_id'] = null;
            }
        }

        if (! empty($filters['street_id'])) {
            $streetWardId = \App\Models\Street::query()->whereKey((int) $filters['street_id'])->value('ward_id');
            $allowedWard = $bounds['ward_id'] ?: ($filters['ward_id'] ?? null);
            if (! $streetWardId || ! $allowedWard || (int) $streetWardId !== (int) $allowedWard) {
                if ($bounds['ward_id'] && (int) $streetWardId === (int) $bounds['ward_id']) {
                    // ok
                } elseif ($bounds['council_id'] && $streetWardId) {
                    $ok = Ward::query()
                        ->whereKey($streetWardId)
                        ->where('council_id', $bounds['council_id'])
                        ->exists();
                    if (! $ok) {
                        $filters['street_id'] = null;
                    }
                } elseif ($bounds['region_id'] && $streetWardId) {
                    $ok = Ward::query()
                        ->whereKey($streetWardId)
                        ->whereHas('council.district', fn ($q) => $q->where('region_id', $bounds['region_id']))
                        ->exists();
                    if (! $ok) {
                        $filters['street_id'] = null;
                    }
                } else {
                    $filters['street_id'] = null;
                }
            }
        }

        return $filters;
    }

    public function assertRegionAllowed(int $regionId, ?User $user = null): void
    {
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return;
        }

        if (($bounds['empty'] ?? false) || (int) $bounds['region_id'] !== $regionId) {
            abort(403);
        }
    }

    public function assertDistrictAllowed(int $districtId, ?User $user = null): void
    {
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return;
        }

        if ($bounds['empty'] ?? false) {
            abort(403);
        }

        if ($bounds['district_id'] && (int) $bounds['district_id'] !== $districtId) {
            abort(403);
        }

        $district = District::query()->findOrFail($districtId);
        if ((int) $district->region_id !== (int) $bounds['region_id']) {
            abort(403);
        }
    }

    public function assertCouncilAllowed(int $councilId, ?User $user = null): void
    {
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return;
        }

        if ($bounds['empty'] ?? false) {
            abort(403);
        }

        if ($bounds['council_id'] && (int) $bounds['council_id'] !== $councilId) {
            abort(403);
        }

        $council = Council::query()->with('district')->findOrFail($councilId);
        if ((int) $council->district?->region_id !== (int) $bounds['region_id']) {
            abort(403);
        }
    }

    public function assertWardAllowed(int $wardId, ?User $user = null): void
    {
        $bounds = $this->zoneBounds($user);

        if ($bounds === null) {
            return;
        }

        if ($bounds['empty'] ?? false) {
            abort(403);
        }

        if ($bounds['ward_id'] && (int) $bounds['ward_id'] !== $wardId) {
            abort(403);
        }

        $ward = Ward::query()->with('council.district')->findOrFail($wardId);

        if ($bounds['council_id'] && (int) $ward->council_id !== (int) $bounds['council_id']) {
            abort(403);
        }

        if ((int) $ward->council?->district?->region_id !== (int) $bounds['region_id']) {
            abort(403);
        }
    }
}
