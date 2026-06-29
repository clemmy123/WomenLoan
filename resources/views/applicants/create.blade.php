@extends('layouts.app')

@section('title', __('applicants.title'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('applicants.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">&larr; {{ __('common.back_to_list') }}</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('applicants.create_title') }}</h1>
        <p class="mt-1 text-sm text-gray-600 font-normal">{{ __('applicants.create_subtitle') }}</p>
    </div>

    @if ($errors->any())
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">
            <span class="font-semibold">{{ __('common.errors_below') }}</span>
            <ul class="mt-2 list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('applicants.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="bg-white p-6 rounded-xl border border-gray-200  space-y-6">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_identification') }}</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label for="first_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.first_name') }}</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" class="w-full bg-gray-50 border @error('first_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('first_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="middle_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.middle_name') }}</label>
                    <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}" class="w-full bg-gray-50 border @error('middle_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('middle_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.last_name') }}</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" class="w-full bg-gray-50 border @error('last_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('last_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                <div>
                    <label for="nin" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.nin') }}</label>
                    <input type="text" name="nin" id="nin" value="{{ old('nin') }}" class="w-full bg-gray-50 border @error('nin') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm font-mono tracking-widest text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('nin') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="dob" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.dob') }}</label>
                    <input type="date" name="dob" id="dob" value="{{ old('dob') }}" class="w-full bg-gray-50 border @error('dob') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('dob') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.phone_hint') }}</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="0712345678" class="w-full bg-gray-50 border @error('phone') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full bg-gray-50 border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200  space-y-6">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_demographics') }}</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="sex" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.sex') }}</label>
                    <select name="sex" id="sex" class="w-full bg-gray-50 border @error('sex') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">{{ __('applicants.select_sex') }}</option>
                        <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>{{ __('applicants.male') }}</option>
                        <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>{{ __('applicants.female') }}</option>
                    </select>
                    @error('sex') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="marital_status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.marital_status') }}</label>
                    <input type="text" name="marital_status" id="marital_status" value="{{ old('marital_status') }}" placeholder="{{ __('applicants.marital_status_hint') }}" class="w-full bg-gray-50 border @error('marital_status') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('marital_status') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nationality" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.nationality') }}</label>
                    <input type="text" name="nationality" id="nationality" value="{{ old('nationality', 'Tanzanian') }}" class="w-full bg-gray-50 border @error('nationality') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('nationality') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="region_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.residential_region') }}</label>
                    <select id="region_select" name="region_id" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- {{ __('geo.select_region') }} --</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="district_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.district') }}</label>
                    <select id="district_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                        <option value="">-- {{ __('geo.select_district') }} --</option>
                    </select>
                </div>

                <div>
                    <label for="council_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.council') }}</label>
                    <select id="council_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                        <option value="">-- {{ __('geo.select_council') }} --</option>
                    </select>
                </div>

                <div>
                    <label for="ward_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.ward') }}</label>
                    <select id="ward_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                        <option value="">-- {{ __('geo.select_ward') }} --</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="street_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.street') }}</label>
                    <select id="street_select" name="location_id" class="w-full bg-gray-50 border @error('location_id') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                        <option value="">-- {{ __('geo.select_street') }} --</option>
                    </select>
                    @error('location_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-6">
            <a href="{{ route('applicants.index') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700  hover:bg-gray-50 transition-all">{{ __('common.cancel') }}</a>
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white  hover:bg-indigo-500 transition-all">{{ __('applicants.save_profile') }}</button>
        </div>
    </form>
</div>

@push('scripts')
@php
    $geoCascadeLabels = [
        'district' => __('geo.select_district'),
        'council' => __('geo.select_council'),
        'ward' => __('geo.select_ward'),
        'street' => __('geo.select_street'),
    ];
    $geoCascadeOldValues = [
        'region' => old('region_id'),
        'district' => old('district_id'),
        'council' => old('council_id'),
        'ward' => old('ward_id'),
        'street' => old('location_id'),
    ];
@endphp
<script type="application/json" id="geo-api-config">@json($geoApi)</script>
<script type="application/json" id="geo-cascade-labels">@json($geoCascadeLabels)</script>
<script type="application/json" id="geo-cascade-old-values">@json($geoCascadeOldValues)</script>
@vite(['resources/js/pages/geo-cascade.js'])
@endpush
@endsection