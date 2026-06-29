<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Council;
use App\Models\District;
use App\Models\Region;
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
}
