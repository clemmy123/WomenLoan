@php
    $selectedRoleForGeo = old('role', old('roles.0', ($userRoles ?? [])[0] ?? null));
    $rolesForGeoBoot = filled($selectedRoleForGeo) ? [(string) $selectedRoleForGeo] : [];
    $staffGeoBoot = \App\Support\StaffZone::formCascadeBoot(
        $user ?? null,
        $rolesForGeoBoot,
        $regions ?? []
    );
    $geoHasError = $errors->has('zone_id') || $errors->has('zone_type');
@endphp

<div
    id="staff-geo-section"
    class="app-card app-card-padded mt-6 {{ $geoHasError ? 'ring-2 ring-red-400' : '' }}"
    data-staff-geo-section
    data-staff-geo-boot='@json($staffGeoBoot)'
    @if($geoHasError) data-error-anchor @endif
    hidden
>
    <h3 class="font-bold text-slate-900 mb-1">
        {{ __('admin.geo_zone') }} @include('partials.required-mark')
    </h3>
    <p class="text-sm text-slate-500 mb-2" data-staff-geo-hint>{{ $staffGeoBoot['labels']['geo_hint'] ?? '' }}</p>
    <p class="text-xs font-medium text-amber-700 dark:text-amber-300 mb-4" data-staff-geo-required-note hidden>
        {{ __('admin.geo_zone_required') }}
    </p>

    <input type="hidden" name="zone_type" data-staff-zone-type value="{{ old('zone_type', $staffGeoBoot['zoneType'] ?? '') }}">
    <input type="hidden" name="zone_id" data-staff-zone-id value="{{ old('zone_id', $staffGeoBoot['zoneId'] ?? '') }}">
    <input type="hidden" name="cascade_region_id" data-staff-cascade-region value="{{ old('cascade_region_id', $staffGeoBoot['selectedRegion'] ?? '') }}">
    <input type="hidden" name="cascade_district_id" data-staff-cascade-district value="{{ old('cascade_district_id', $staffGeoBoot['selectedDistrict'] ?? '') }}">
    <input type="hidden" name="cascade_council_id" data-staff-cascade-council value="{{ old('cascade_council_id', $staffGeoBoot['selectedCouncil'] ?? '') }}">
    <input type="hidden" name="cascade_ward_id" data-staff-cascade-ward value="{{ old('cascade_ward_id', $staffGeoBoot['selectedWard'] ?? '') }}">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="app-label" for="staff_region_id">{{ __('geo.region') }} @include('partials.required-mark')</label>
            <select
                id="staff_region_id"
                class="app-select {{ $geoHasError && ! old('cascade_region_id') && ! old('zone_id') ? 'border-red-400' : '' }}"
                data-geo-step="region"
            >
                <option value="">{{ __('admin.select_region') }}</option>
                @foreach($staffGeoBoot['regions'] ?? [] as $region)
                    <option value="{{ $region['id'] }}" @selected((string) old('cascade_region_id', $staffGeoBoot['selectedRegion'] ?? '') === (string) $region['id'])>{{ $region['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div data-geo-wrap="district" hidden>
            <label class="app-label" for="staff_district_id">{{ __('geo.district') }} @include('partials.required-mark')</label>
            <select
                id="staff_district_id"
                class="app-select"
                data-geo-step="district"
                disabled
            >
                <option value="">{{ __('geo.select_district') }}</option>
            </select>
        </div>

        <div data-geo-wrap="council" hidden>
            <label class="app-label" for="staff_council_id">{{ __('geo.council') }} @include('partials.required-mark')</label>
            <select
                id="staff_council_id"
                class="app-select"
                data-geo-step="council"
                disabled
            >
                <option value="">{{ __('admin.select_council') }}</option>
            </select>
        </div>

        <div data-geo-wrap="ward" hidden>
            <label class="app-label" for="staff_ward_id">{{ __('geo.ward') }} @include('partials.required-mark')</label>
            <select
                id="staff_ward_id"
                class="app-select"
                data-geo-step="ward"
                disabled
            >
                <option value="">{{ __('admin.select_ward') }}</option>
            </select>
        </div>
    </div>

    <p class="mt-2 text-xs font-medium text-red-600" data-staff-gap-message hidden></p>
    @error('zone_type')
        <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
    @error('zone_id')
        <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
