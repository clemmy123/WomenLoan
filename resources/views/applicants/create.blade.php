@extends('layouts.app')

@section('title', __('applicants.title'))

@section('content')
<div class="page page-narrow">
    <div class="flex items-center space-x-4">
        <a href="{{ route('applicants.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">&larr; {{ __('common.back_to_list') }}</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('applicants.create_title') }}</h1>
        <p class="mt-1 text-sm text-gray-600 font-normal">{{ __('applicants.create_subtitle') }}</p>
    </div>

    <form action="{{ route('applicants.store') }}" method="POST" class="space-y-6">
        @csrf

        @include('applicants._form', ['applicant' => null, 'regionId' => null, 'districtId' => null, 'councilId' => null, 'wardId' => null, 'streetId' => null])

        <div class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-6">
            <a href="{{ route('applicants.index') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all">{{ __('common.cancel') }}</a>
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-all">{{ __('applicants.save_profile') }}</button>
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
