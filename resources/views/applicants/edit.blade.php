@extends('layouts.app')

@section('title', __('applicants.edit_title'))

@section('content')
<div class="page page-narrow">
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

        @include('applicants._form', [
            'regionId' => $region?->id,
            'districtId' => $district?->id,
            'councilId' => $council?->id,
            'wardId' => $ward?->id,
            'streetId' => $location?->id,
        ])

        <div class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-6">
            <a href="{{ route('applicants.show', $applicant) }}" class="app-btn app-btn-outline">{{ __('common.cancel') }}</a>
            <button type="submit" class="app-btn app-btn-warning">{{ __('applicants.update_profile') }}</button>
        </div>
    </form>
</div>

@include('applicants._geo_scripts', [
    'regionId' => $region?->id,
    'districtId' => $district?->id,
    'councilId' => $council?->id,
    'wardId' => $ward?->id,
    'streetId' => $location?->id,
])
@endsection
