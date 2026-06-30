@push('scripts')
@php
    $geoCascadeLabels = [
        'district' => __('geo.select_district'),
        'council' => __('geo.select_council'),
        'ward' => __('geo.select_ward'),
        'street' => __('geo.select_street'),
    ];
    $geoCascadeOldValues = [
        'region' => old('region_id', $regionId ?? null),
        'district' => old('district_id', $districtId ?? null),
        'council' => old('council_id', $councilId ?? null),
        'ward' => old('ward_id', $wardId ?? null),
        'street' => old('location_id', $streetId ?? null),
    ];
@endphp
<script type="application/json" id="geo-api-config">@json($geoApi)</script>
<script type="application/json" id="geo-cascade-labels">@json($geoCascadeLabels)</script>
<script type="application/json" id="geo-cascade-old-values">@json($geoCascadeOldValues)</script>
@vite(['resources/js/pages/geo-cascade.js'])
@endpush
