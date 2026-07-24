@php
    $staffGeoBoot = \App\Support\StaffZone::formCascadeBoot(
        $user ?? null,
        array_values(old('roles', $userRoles ?? [])),
        $regions ?? []
    );
@endphp

<div
    class="app-card app-card-padded mt-6"
    x-data="userGeoZoneForm(@js($staffGeoBoot))"
    x-cloak
    x-show="showGeo"
>
    <h3 class="font-bold text-slate-900 mb-1">{{ __('admin.geo_zone') }}</h3>
    <p class="text-sm text-slate-500 mb-4" x-text="labels.geo_hint"></p>

    <input type="hidden" name="zone_type" :value="zoneType">
    <input type="hidden" name="zone_id" :value="zoneId">
    <input type="hidden" name="cascade_region_id" :value="selectedRegion">
    <input type="hidden" name="cascade_district_id" :value="selectedDistrict">
    <input type="hidden" name="cascade_council_id" :value="selectedCouncil">
    <input type="hidden" name="cascade_ward_id" :value="selectedWard">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="app-label" for="staff_region_id">{{ __('geo.region') }} @include('partials.required-mark')</label>
            <select
                id="staff_region_id"
                class="app-select"
                x-model="selectedRegion"
                @change="onRegionChange()"
                :required="showGeo"
            >
                <option value="">{{ __('admin.select_region') }}</option>
                <template x-for="item in regions" :key="'r-'+item.id">
                    <option :value="String(item.id)" x-text="item.name"></option>
                </template>
            </select>
        </div>

        <div x-show="showDistrict" x-cloak>
            <label class="app-label" for="staff_district_id">{{ __('geo.district') }} @include('partials.required-mark')</label>
            <select
                id="staff_district_id"
                class="app-select"
                x-model="selectedDistrict"
                @change="onDistrictChange()"
                :required="showDistrict"
                :disabled="! selectedRegion"
            >
                <option value="">{{ __('geo.select_district') }}</option>
                <template x-for="item in districts" :key="'d-'+item.id">
                    <option :value="String(item.id)" x-text="item.name"></option>
                </template>
            </select>
        </div>

        <div x-show="showCouncil" x-cloak>
            <label class="app-label" for="staff_council_id">{{ __('geo.council') }} @include('partials.required-mark')</label>
            <select
                id="staff_council_id"
                class="app-select"
                x-model="selectedCouncil"
                @change="onCouncilChange()"
                :required="showCouncil"
                :disabled="! selectedDistrict"
            >
                <option value="">{{ __('admin.select_council') }}</option>
                <template x-for="item in councils" :key="'c-'+item.id">
                    <option :value="String(item.id)" x-text="item.name"></option>
                </template>
            </select>
        </div>

        <div x-show="showWard" x-cloak>
            <label class="app-label" for="staff_ward_id">{{ __('geo.ward') }} @include('partials.required-mark')</label>
            <select
                id="staff_ward_id"
                class="app-select"
                x-model="selectedWard"
                @change="onWardChange()"
                :required="showWard"
                :disabled="! selectedCouncil"
            >
                <option value="">{{ __('admin.select_ward') }}</option>
                <template x-for="item in wards" :key="'w-'+item.id">
                    <option :value="String(item.id)" x-text="item.name"></option>
                </template>
            </select>
        </div>
    </div>

    @error('zone_type') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
    @error('zone_id') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
</div>
