@extends('layouts.app')

@section('title', __('applicants.title'))

@section('content')
@php
    $manualEntry = $manualEntry ?? false;
    $lockNidaFields = $lockNidaFields ?? false;
    $selfServiceOnboarding = $selfServiceOnboarding ?? false;
    $backUrl = $selfServiceOnboarding ? route('dashboard') : route('applicants.index');
    $backLabel = $selfServiceOnboarding ? __('nav.dashboard') : __('common.back_to_list');

    $initialOnboardingStep = 1;
    if ($selfServiceOnboarding) {
        if ($errors->has('preferred_loan_type')) {
            $initialOnboardingStep = 1;
        } elseif ($errors->hasAny(['marital_status', 'has_disability'])) {
            $initialOnboardingStep = 2;
        } elseif ($errors->has('location_id')) {
            $initialOnboardingStep = 3;
        } elseif (filled(old('preferred_loan_type'))) {
            $initialOnboardingStep = filled(old('marital_status')) && filled(old('has_disability')) ? 3 : 2;
        }
    }

    $onboardingWizardConfig = [
        'initialStep' => $initialOnboardingStep,
        'loanType' => old('preferred_loan_type', ''),
        'maritalStatus' => old('marital_status', ''),
        'hasDisability' => old('has_disability', ''),
        'locationId' => old('location_id', ''),
    ];

    $onboardingWizardSteps = [
        ['icon' => 'amount', 'title' => __('applicants.onboarding_wizard_steps.loan_type')],
        ['icon' => 'guarantor', 'title' => __('applicants.onboarding_wizard_steps.personal')],
        ['icon' => 'business', 'title' => __('applicants.onboarding_wizard_steps.address')],
    ];
@endphp

<div
    class="app-page app-page-narrow"
    @if($selfServiceOnboarding)
        data-applicant-onboarding-wizard
        data-onboarding-config='@json($onboardingWizardConfig)'
    @endif
>
    @if($selfServiceOnboarding)
        @include('partials.wizard-loading')
    @endif
    <div class="flex items-center space-x-4">
        <a href="{{ $backUrl }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">&larr; {{ $backLabel }}</a>
    </div>

    <div data-onboarding-heading>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
            {{ $manualEntry ? __('applicants.add_new') : ($selfServiceOnboarding ? __('nav.register_applicant') : __('applicants.create_title')) }}
        </h1>
        <p class="mt-1 text-sm text-gray-600 font-normal">
            @if ($manualEntry)
                {{ __('applicants.manual_entry_subtitle') }}
            @elseif ($selfServiceOnboarding)
                {{ __('applicants.onboarding_subtitle') }}
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

    @if($selfServiceOnboarding)
        <div class="app-card app-card-padded mt-6">
            <x-wizard-stepper :steps="$onboardingWizardSteps" />
        </div>
    @endif

    <form
        action="{{ route('applicants.store') }}"
        method="POST"
        class="space-y-6 {{ $selfServiceOnboarding ? 'mt-6' : '' }}"
    >
        @csrf

        @include('applicants._form', [
            'lockRegistrationFields' => $lockRegistrationFields ?? false,
            'lockNidaFields' => $lockNidaFields,
            'selfServiceOnboarding' => $selfServiceOnboarding,
            'initialOnboardingStep' => $initialOnboardingStep,
            'regionId' => null,
            'districtId' => null,
            'councilId' => null,
            'wardId' => null,
            'streetId' => null,
        ])

        @if($selfServiceOnboarding)
            <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-6">
                <div>
                    <button
                        type="button"
                        data-onboarding-back
                        class="app-btn app-btn-outline"
                        hidden
                    >
                        {{ __('common.back') }}
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ $backUrl }}" class="app-btn app-btn-outline">{{ __('common.cancel') }}</a>
                    <button
                        type="button"
                        data-onboarding-next
                        class="app-btn app-btn-primary transition-all"
                    >
                        {{ __('common.next') }}
                    </button>
                    <button
                        type="submit"
                        data-onboarding-submit
                        class="app-btn app-btn-primary transition-all"
                        hidden
                    >
                        {{ __('applicants.save_profile') }}
                    </button>
                </div>
            </div>
        @else
            <div class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-6">
                <a href="{{ $backUrl }}" class="app-btn app-btn-outline">{{ __('common.cancel') }}</a>
                <button type="submit" class="app-btn app-btn-primary">{{ __('applicants.save_profile') }}</button>
            </div>
        @endif
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
