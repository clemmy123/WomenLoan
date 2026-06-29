@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('applicants.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">&larr; Back to Registry</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Register New Applicant</h1>
        <p class="mt-1 text-sm text-gray-600 font-normal">Populate system core schema parameters manually without external sync handshake structures.</p>
    </div>

    {{-- MWONGOZO: Sehemu hii itakusaidia kuona makosa yote ya ki-validation mara moja --}}
    @if ($errors->any())
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">
            <span class="font-semibold">Tafadhali rekebisha makosa yafuatayo:</span>
            <ul class="mt-2 list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('applicants.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-6">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">Core Mandatory Identification</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label for="first_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">First Name</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" class="w-full bg-gray-50 border @error('first_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('first_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="middle_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}" class="w-full bg-gray-50 border @error('middle_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('middle_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Last Name / Surname</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" class="w-full bg-gray-50 border @error('last_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('last_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                <div>
                    <label for="nin" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">NIN (20 Digits)</label>
                    <input type="text" name="nin" id="nin" value="{{ old('nin') }}" class="w-full bg-gray-50 border @error('nin') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm font-mono tracking-widest text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('nin') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="dob" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Date of Birth</label>
                    <input type="date" name="dob" id="dob" value="{{ old('dob') }}" class="w-full bg-gray-50 border @error('dob') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('dob') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Phone (e.g. 07xxxxxxxx)</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="0712345678" class="w-full bg-gray-50 border @error('phone') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full bg-gray-50 border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-6">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">Profile Demographic Metadata & Location</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="sex" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Sex</label>
                    <select name="sex" id="sex" class="w-full bg-gray-50 border @error('sex') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Sex</option>
                        <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('sex') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="marital_status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Marital Status</label>
                    <input type="text" name="marital_status" id="marital_status" value="{{ old('marital_status') }}" placeholder="Single, Married..." class="w-full bg-gray-50 border @error('marital_status') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('marital_status') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nationality" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Nationality</label>
                    <input type="text" name="nationality" id="nationality" value="{{ old('nationality', 'Tanzanian') }}" class="w-full bg-gray-50 border @error('nationality') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('nationality') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="region_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Residential Region</label>
                    <select id="region_select" name="region_id" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Select Region --</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="district_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">District</label>
                    <select id="district_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                        <option value="">-- Select District --</option>
                    </select>
                </div>

                <div>
                    <label for="council_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Council</label>
                    <select id="council_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                        <option value="">-- Select Council --</option>
                    </select>
                </div>

                <div>
                    <label for="ward_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Ward</label>
                    <select id="ward_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                        <option value="">-- Select Ward --</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="street_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Assigned Residential Street / Village</label>
                    <select id="street_select" name="location_id" class="w-full bg-gray-50 border @error('location_id') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                        <option value="">-- Select Street / Village --</option>
                    </select>
                    @error('location_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-6">
            <a href="{{ route('applicants.index') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all">Cancel</a>
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all">Save System Profile</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const regionSelect = document.getElementById('region_select');
    const districtSelect = document.getElementById('district_select');
    const councilSelect = document.getElementById('council_select');
    const wardSelect = document.getElementById('ward_select');
    const streetSelect = document.getElementById('street_select');

    // Thamani za zamani (Old Values kutoka Laravel Validation fail)
    const oldRegion = "{{ old('region_id') }}";
    const oldDistrict = "{{ old('district_id') }}"; // Kama huna hizi kwenye request unaweza kuziacha
    const oldCouncil = "{{ old('council_id') }}";
    const oldWard = "{{ old('ward_id') }}";
    const oldStreet = "{{ old('location_id') }}";

    function resetDropdown(dropdown, placeholder) {
        dropdown.innerHTML = `<option value="">-- ${placeholder} --</option>`;
        dropdown.disabled = true;
    }

    // 1. Region -> Districts
    regionSelect.addEventListener('change', function (e, selectedId = null) {
        const val = selectedId || this.value;
        if (!val) return;

        resetDropdown(districtSelect, 'Select District');
        resetDropdown(councilSelect, 'Select Council');
        resetDropdown(wardSelect, 'Select Ward');
        resetDropdown(streetSelect, 'Select Street / Village');

        fetch(`/api/regions/${val}/districts`)
            .then(res => res.json())
            .then(data => {
                districtSelect.disabled = false;
                data.forEach(item => {
                    const isSelected = item.id == oldDistrict ? 'selected' : '';
                    districtSelect.innerHTML += `<option value="${item.id}" ${isSelected}>${item.name}</option>`;
                });
                if(oldDistrict) districtSelect.dispatchEvent(new Event('change'));
            });
    });

    // 2. District -> Councils
    districtSelect.addEventListener('change', function () {
        if (!this.value) return;
        resetDropdown(councilSelect, 'Select Council');
        resetDropdown(wardSelect, 'Select Ward');
        resetDropdown(streetSelect, 'Select Street / Village');

        fetch(`/api/districts/${this.value}/councils`)
            .then(res => res.json())
            .then(data => {
                councilSelect.disabled = false;
                data.forEach(item => {
                    const isSelected = item.id == oldCouncil ? 'selected' : '';
                    councilSelect.innerHTML += `<option value="${item.id}" ${isSelected}>${item.name} (${item.code})</option>`;
                });
                if(oldCouncil) councilSelect.dispatchEvent(new Event('change'));
            });
    });

    // 3. Council -> Wards
    councilSelect.addEventListener('change', function () {
        if (!this.value) return;
        resetDropdown(wardSelect, 'Select Ward');
        resetDropdown(streetSelect, 'Select Street / Village');

        fetch(`/api/councils/${this.value}/wards`)
            .then(res => res.json())
            .then(data => {
                wardSelect.disabled = false;
                data.forEach(item => {
                    const isSelected = item.id == oldWard ? 'selected' : '';
                    wardSelect.innerHTML += `<option value="${item.id}" ${isSelected}>${item.name}</option>`;
                });
                if(oldWard) wardSelect.dispatchEvent(new Event('change'));
            });
    });

    // 4. Ward -> Streets
    wardSelect.addEventListener('change', function () {
        if (!this.value) return;
        resetDropdown(streetSelect, 'Select Street / Village');

        fetch(`/api/wards/${this.value}/streets`)
            .then(res => res.json())
            .then(data => {
                streetSelect.disabled = false;
                data.forEach(item => {
                    const isSelected = item.id == oldStreet ? 'selected' : '';
                    streetSelect.innerHTML += `<option value="${item.id}" ${isSelected}>${item.name}</option>`;
                });
            });
    });

    // Anzisha mnyororo kama kuna "old values" zilirudishwa baada ya validation kufeli
    if(oldRegion) {
        regionSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection