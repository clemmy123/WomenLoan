@extends('layouts.app')

@section('title', __('nav.my_profile'))

@section('content')
<div class="app-page app-page-narrow">
    <div class="app-page-header">
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white transition-colors">&larr; {{ __('nav.dashboard') }}</a>
    </div>

    <div>
        <h1 class="app-page-title">{{ __('nav.my_profile') }}</h1>
        <p class="app-page-subtitle">{{ __('profile.registration_details_hint') }}</p>
    </div>

    <div class="app-card app-card-padded">
        @include('partials.registration-identity-card', [
            'user' => $user,
            'applicant' => $applicant,
            'lockNidaFields' => $lockNidaFields,
        ])
    </div>

    @if($nav['needsProfileCompletion'] ?? false)
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-5">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ __('dashboard.complete_profile') }}</p>
            <a href="{{ route('applicants.create') }}" class="app-btn app-btn-primary">{{ __('nav.register_applicant') }}</a>
        </div>
    @endif
</div>
@endsection
