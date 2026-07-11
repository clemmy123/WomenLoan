@php
    $geoLocks = ($geoBounds ?? [])['lock'] ?? [];
@endphp

<div class="wizard-field">
    <label class="app-label" for="region_id">{{ __('geo.region') }}</label>
    @if(!empty($geoLocks['region_id']))
        <input type="hidden" name="region_id" value="{{ $geoLocks['region_id'] }}">
    @endif
    <select
        name="region_id"
        id="region_id"
        x-model="selectedRegion"
        @change="onRegionChange()"
        class="app-select"
        @disabled(!empty($geoLocks['region_id']))
    >
        @if(empty($geoLocks['region_id']))
            <option value="">{{ __('geo.select_region') }}</option>
        @endif
        @foreach($regions as $region)
            <option value="{{ $region->id }}">{{ $region->name }}</option>
        @endforeach
    </select>
</div>
<div class="wizard-field">
    <label class="app-label" for="district_id">{{ __('geo.district') }}</label>
    @if(!empty($geoLocks['district_id']))
        <input type="hidden" name="district_id" value="{{ $geoLocks['district_id'] }}">
    @endif
    <select
        name="district_id"
        id="district_id"
        x-model="selectedDistrict"
        @change="onDistrictChange()"
        class="app-select"
        @disabled(!empty($geoLocks['district_id']))
    >
        @if(empty($geoLocks['district_id']))
            <option value="">{{ __('geo.select_district') }}</option>
        @endif
        <template x-for="district in districts" :key="district.id">
            <option :value="String(district.id)" x-text="district.name"></option>
        </template>
    </select>
</div>
<div class="wizard-field">
    <label class="app-label" for="council_id">{{ __('geo.council') }}</label>
    @if(!empty($geoLocks['council_id']))
        <input type="hidden" name="council_id" value="{{ $geoLocks['council_id'] }}">
    @endif
    <select
        name="council_id"
        id="council_id"
        x-model="selectedCouncil"
        @change="onCouncilChange()"
        class="app-select"
        @disabled(!empty($geoLocks['council_id']))
    >
        @if(empty($geoLocks['council_id']))
            <option value="">{{ __('geo.select_council') }}</option>
        @endif
        <template x-for="council in councils" :key="council.id">
            <option :value="String(council.id)" x-text="council.name"></option>
        </template>
    </select>
</div>
<div class="wizard-field">
    <label class="app-label" for="ward_id">{{ __('geo.ward') }}</label>
    @if(!empty($geoLocks['ward_id']))
        <input type="hidden" name="ward_id" value="{{ $geoLocks['ward_id'] }}">
    @endif
    <select
        name="ward_id"
        id="ward_id"
        x-model="selectedWard"
        @change="onWardChange()"
        class="app-select"
        @disabled(!empty($geoLocks['ward_id']))
    >
        @if(empty($geoLocks['ward_id']))
            <option value="">{{ __('geo.select_ward') }}</option>
        @endif
        <template x-for="ward in wards" :key="ward.id">
            <option :value="String(ward.id)" x-text="ward.name"></option>
        </template>
    </select>
</div>
<div class="wizard-field">
    <label class="app-label" for="street_id">{{ __('geo.street') }}</label>
    <select name="street_id" id="street_id" x-model="selectedStreet" class="app-select">
        <option value="">{{ __('geo.select_street') }}</option>
        <template x-for="street in streets" :key="street.id">
            <option :value="String(street.id)" x-text="street.name"></option>
        </template>
    </select>
</div>
