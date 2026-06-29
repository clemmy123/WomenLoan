@extends('layouts.app')

@section('title', __('applicants.edit_title'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center">
        <a href="{{ route('applicants.show', $applicant) }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">&larr; {{ __('applicants.back_to_profile') }}</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('applicants.edit_title') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('applicants.edit_subtitle') }}</p>
    </div>

    <form action="{{ route('applicants.update', $applicant) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white p-6 rounded-xl border border-gray-200  space-y-6">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_identity') }}</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="full_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('common.full_name') }}</label>
                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $applicant->full_name) }}" class="w-full bg-gray-50 border @error('full_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nin" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.nin') }}</label>
                    <input type="text" name="nin" id="nin" value="{{ old('nin', $applicant->nin) }}" class="w-full bg-gray-50 border @error('nin') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm font-mono tracking-widest text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('nin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="dob" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.dob') }}</label>
                    <input type="date" name="dob" id="dob" value="{{ old('dob', $applicant->dob instanceof \DateTime ? $applicant->dob->format('Y-m-d') : $applicant->dob) }}" class="w-full bg-gray-50 border @error('dob') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('dob') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('common.phone') }}</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $applicant->phone) }}" class="w-full bg-gray-50 border @error('phone') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $applicant->email) }}" class="w-full bg-gray-50 border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200  space-y-6">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_demographics') }}</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="sex" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.sex') }}</label>
                    <select name="sex" id="sex" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900">
                        <option value="Male" {{ old('sex', $applicant->sex) == 'Male' ? 'selected' : '' }}>{{ __('applicants.male') }}</option>
                        <option value="Female" {{ old('sex', $applicant->sex) == 'Female' ? 'selected' : '' }}>{{ __('applicants.female') }}</option>
                    </select>
                </div>

                <div>
                    <label for="marital_status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.marital_status') }}</label>
                    <input type="text" name="marital_status" id="marital_status" value="{{ old('marital_status', $applicant->marital_status) }}" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900">
                </div>

                <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.region') }}</label>
                        <select id="region_id" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm">
                            <option value="">{{ __('geo.select_region') }}</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->id }}" {{ $applicant->location?->ward?->council?->district?->region_id == $r->id ? 'selected' : '' }}>
                                    {{ $r->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="location_id" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.street') }}</label>
                        <select name="location_id" id="location_id" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm">
                            @if($applicant->location)
                                <option value="{{ $applicant->location_id }}" selected>{{ $applicant->location->name }}</option>
                            @else
                                <option value="">{{ __('geo.select_street') }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div>
                    <label for="nationality" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.nationality') }}</label>
                    <input type="text" name="nationality" id="nationality" value="{{ old('nationality', $applicant->nationality) }}" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-6">
            <a href="{{ route('applicants.show', $applicant) }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700  hover:bg-gray-50">{{ __('common.cancel') }}</a>
            <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white  hover:bg-amber-500">{{ __('applicants.update_profile') }}</button>
        </div>
    </form>
</div>
@endsection
