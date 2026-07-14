@extends('layouts.app')

@section('title', __('applicants.title'))

@section('content')
@php
    $manualEntry = $manualEntry ?? false;
    $lockNidaFields = $lockNidaFields ?? false;
@endphp

<div class="page page-narrow">
    <div class="flex items-center space-x-4">
        <a href="{{ route('applicants.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">&larr; {{ __('common.back_to_list') }}</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
            {{ $manualEntry ? __('applicants.add_new') : __('applicants.create_title') }}
        </h1>
        <p class="mt-1 text-sm text-gray-600 font-normal">
            @if ($manualEntry)
                {{ __('applicants.manual_entry_subtitle') }}
            @elseif ($lockNidaFields)
                {{ __('nida.profile_constants_hint') }}
            @else
                {{ __('applicants.create_subtitle') }}
            @endif
        </p>
        @if ($manualEntry)
            <p class="mt-2 inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">
                {{ __('applicants.manual_mode') }}
            </p>
        @endif
    </div>

    <form action="{{ route('applicants.store') }}" method="POST" class="space-y-6">
        @csrf

        @include('applicants._form', [
            'lockRegistrationFields' => $lockRegistrationFields ?? false,
            'lockNidaFields' => $lockNidaFields,
            'regionId' => null,
            'districtId' => null,
            'councilId' => null,
            'wardId' => null,
            'streetId' => null,
        ])

        <div class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-6">
            <a href="{{ route('applicants.index') }}" class="app-btn app-btn-outline">{{ __('common.cancel') }}</a>
            <button type="submit" class="app-btn app-btn-primary">{{ __('applicants.save_profile') }}</button>
        </div>
    </form>
</div>

@include('applicants._geo_scripts', [
    'regionId' => null,
    'districtId' => null,
    'councilId' => null,
    'wardId' => null,
    'streetId' => null,
])
@endsection
