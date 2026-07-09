@extends('layouts.app')

@section('title', __('applicants.show_title'))

@section('content')
<div class="page page-medium">
    <div class="page-header">
        <a href="{{ route('applicants.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white transition-colors">&larr; {{ __('applicants.back_to_registry') }}</a>
        <div class="page-actions">
            <a href="{{ route('applicants.edit', $applicant) }}" class="app-btn app-btn-warning">{{ __('applicants.edit_profile') }}</a>
        </div>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">{{ $applicant->full_name }}</h1>
                <p class="page-subtitle font-mono text-xs">
                    {{ __('applicants.system_profile_id', ['id' => $applicant->id]) }}
                    &bull; {{ __('applicants.linked_user', ['name' => $applicant->user?->name ?? __('common.na')]) }}
                </p>
            </div>
            @include('partials.badge', ['variant' => 'light', 'text' => __('applicants.manual_mode'), 'class' => 'mt-2 sm:mt-0'])
        </div>

        <div class="app-card-padded space-y-8">
            <section>
                <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('applicants.section_identification') }}</h2>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('applicants.first_name'), 'value' => $applicant->first_name])
                    @include('partials.detail-field', ['label' => __('applicants.middle_name'), 'value' => $applicant->middle_name])
                    @include('partials.detail-field', ['label' => __('applicants.last_name'), 'value' => $applicant->last_name])
                    @include('partials.detail-field', ['label' => __('applicants.nin'), 'value' => $applicant->nin, 'mono' => true])
                    @include('partials.detail-field', [
                        'label' => __('applicants.dob'),
                        'value' => $applicant->dob
                            ? $applicant->dob->translatedFormat('M d, Y').' ('.__('applicants.age_years', ['age' => $applicant->age() ?? '—']).')'
                            : null,
                    ])
                    @include('partials.detail-field', ['label' => __('applicants.phone_number'), 'value' => $applicant->phone])
                    @include('partials.detail-field', ['label' => __('applicants.email'), 'value' => $applicant->email])
                </div>
            </section>

            <section>
                <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('applicants.section_demographics') }}</h2>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('applicants.sex'), 'value' => $applicant->sex])
                    @include('partials.detail-field', ['label' => __('applicants.marital_status'), 'value' => $applicant->marital_status ? __('applicants.marital_statuses.'.$applicant->marital_status) : null])
                    @include('partials.detail-field', ['label' => __('applicants.has_disability'), 'value' => $applicant->has_disability === null ? null : ($applicant->has_disability ? __('common.yes') : __('common.no'))])
                    @include('partials.detail-field', ['label' => __('applicants.nationality'), 'value' => $applicant->nationality])
                    @include('partials.detail-field', ['label' => __('applicants.preferred_loan_type'), 'value' => $applicant->preferred_loan_type ? __('applicants.loan_types.'.$applicant->preferred_loan_type) : null])
                </div>
            </section>

            <section>
                <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('applicants.section_residential_address') }}</h2>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('geo.region'), 'value' => $region?->name])
                    @include('partials.detail-field', ['label' => __('geo.district'), 'value' => $district?->name])
                    @include('partials.detail-field', ['label' => __('geo.council'), 'value' => $council?->name])
                    @include('partials.detail-field', ['label' => __('geo.ward'), 'value' => $ward?->name])
                    @include('partials.detail-field', ['label' => __('geo.street'), 'value' => $location?->name])
                    @include('partials.detail-field', ['label' => __('applicants.postal_code'), 'value' => $applicant->postal_code])
                    @include('partials.detail-field', ['label' => __('applicants.po_box'), 'value' => $applicant->po_box])
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
