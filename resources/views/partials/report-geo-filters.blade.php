@php
    $geoLocks = ($geoBounds ?? [])['lock'] ?? [];
    $allowAllRegions = $allowAllRegions ?? true;
@endphp

<div class="wizard-field">
    <label class="app-label" for="region_id">{{ __('geo.region') }}</label>
    <div class="app-filter-control" :class="{ 'has-clear': selectedRegion && !isLocked('region_id') }">
        @if(! empty($geoLocks['region_id']))
            <input type="hidden" name="region_id" value="{{ $geoLocks['region_id'] }}">
        @endif
        <select
            name="region_id"
            id="region_id"
            x-model="selectedRegion"
            @change="onRegionChange()"
            class="app-select"
            @disabled(! empty($geoLocks['region_id']))
        >
            @if(empty($geoLocks['region_id']) && $allowAllRegions)
                <option value="">{{ __('by_region_reports.all_regions') }}</option>
            @elseif(empty($geoLocks['region_id']))
                <option value="">{{ __('geo.select_region') }}</option>
            @endif
            @foreach($regions as $region)
                <option value="{{ $region->id }}">{{ $region->name }}</option>
            @endforeach
        </select>
        <button
            type="button"
            class="app-filter-clear-inside"
            x-show="selectedRegion && !isLocked('region_id')"
            x-cloak
            @click.prevent="clearRegion()"
            title="{{ __('common.clear') }}"
            aria-label="{{ __('common.clear') }}"
        >
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
            </svg>
        </button>
    </div>
</div>

<div class="wizard-field" x-show="showDistrict" x-cloak>
    <label class="app-label" for="district_id">{{ __('geo.district') }}</label>
    <div class="app-filter-control" :class="{ 'has-clear': !isLocked('district_id') }">
        @if(! empty($geoLocks['district_id']))
            <input type="hidden" name="district_id" value="{{ $geoLocks['district_id'] }}">
        @endif
        <select
            name="district_id"
            id="district_id"
            x-model="selectedDistrict"
            @change="onDistrictChange()"
            class="app-select"
            @disabled(! empty($geoLocks['district_id']))
        >
            @if(empty($geoLocks['district_id']))
                <option value="">{{ __('geo.select_district') }}</option>
            @endif
            <template x-for="district in districts" :key="district.id">
                <option :value="String(district.id)" x-text="district.name"></option>
            </template>
        </select>
        <button
            type="button"
            class="app-filter-clear-inside"
            x-show="!isLocked('district_id')"
            x-cloak
            @click.prevent="clearDistrict()"
            title="{{ __('common.clear') }}"
            aria-label="{{ __('common.clear') }}"
        >
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
            </svg>
        </button>
    </div>
</div>

<div class="wizard-field" x-show="showCouncil" x-cloak>
    <label class="app-label" for="council_id">{{ __('geo.council') }}</label>
    <div class="app-filter-control" :class="{ 'has-clear': !isLocked('council_id') }">
        @if(! empty($geoLocks['council_id']))
            <input type="hidden" name="council_id" value="{{ $geoLocks['council_id'] }}">
        @endif
        <select
            name="council_id"
            id="council_id"
            x-model="selectedCouncil"
            @change="onCouncilChange()"
            class="app-select"
            @disabled(! empty($geoLocks['council_id']))
        >
            @if(empty($geoLocks['council_id']))
                <option value="">{{ __('geo.select_council') }}</option>
            @endif
            <template x-for="council in councils" :key="council.id">
                <option :value="String(council.id)" x-text="council.name"></option>
            </template>
        </select>
        <button
            type="button"
            class="app-filter-clear-inside"
            x-show="!isLocked('council_id')"
            x-cloak
            @click.prevent="clearCouncil()"
            title="{{ __('common.clear') }}"
            aria-label="{{ __('common.clear') }}"
        >
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
            </svg>
        </button>
    </div>
</div>

<div class="wizard-field" x-show="showWard" x-cloak>
    <label class="app-label" for="ward_id">{{ __('geo.ward') }}</label>
    <div class="app-filter-control" :class="{ 'has-clear': !isLocked('ward_id') }">
        @if(! empty($geoLocks['ward_id']))
            <input type="hidden" name="ward_id" value="{{ $geoLocks['ward_id'] }}">
        @endif
        <select
            name="ward_id"
            id="ward_id"
            x-model="selectedWard"
            @change="onWardChange()"
            class="app-select"
            @disabled(! empty($geoLocks['ward_id']))
        >
            @if(empty($geoLocks['ward_id']))
                <option value="">{{ __('geo.select_ward') }}</option>
            @endif
            <template x-for="ward in wards" :key="ward.id">
                <option :value="String(ward.id)" x-text="ward.name"></option>
            </template>
        </select>
        <button
            type="button"
            class="app-filter-clear-inside"
            x-show="!isLocked('ward_id')"
            x-cloak
            @click.prevent="clearWard()"
            title="{{ __('common.clear') }}"
            aria-label="{{ __('common.clear') }}"
        >
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
            </svg>
        </button>
    </div>
</div>

<div class="wizard-field" x-show="showStreet" x-cloak>
    <label class="app-label" for="street_id">{{ __('geo.street') }}</label>
    <div class="app-filter-control has-clear">
        <select name="street_id" id="street_id" x-model="selectedStreet" class="app-select">
            <option value="">{{ __('geo.select_street') }}</option>
            <template x-for="street in streets" :key="street.id">
                <option :value="String(street.id)" x-text="street.name"></option>
            </template>
        </select>
        <button
            type="button"
            class="app-filter-clear-inside"
            @click.prevent="clearStreet()"
            title="{{ __('common.clear') }}"
            aria-label="{{ __('common.clear') }}"
        >
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
            </svg>
        </button>
    </div>
</div>
