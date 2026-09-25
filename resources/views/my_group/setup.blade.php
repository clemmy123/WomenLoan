@extends('layouts.app')

@section('title', __('groups.setup_title'))

@section('content')
<div
    class="app-page app-page-medium"
    data-group-setup
    data-max-adult-dob="{{ now()->subYears(18)->toDateString() }}"
    data-age-template="{{ __('applicants.age_years', ['age' => ':age']) }}"
>
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">{{ __('groups.setup_title') }}</h1>
            <p class="app-page-subtitle">{{ __('groups.setup_subtitle') }}</p>
        </div>
    </div>

    <form action="{{ route('my-group.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="app-card app-card-padded space-y-4">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600">{{ __('groups.group_details') }}</h2>
            <div class="wizard-form-grid wizard-form-grid-2">
                <x-wizard-field :label="__('groups.group_name')" for="name" :required="true">
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="app-input">
                </x-wizard-field>
                <x-wizard-field :label="__('groups.reg_number')" for="registration_number">
                    <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number') }}" class="app-input">
                </x-wizard-field>
                <x-wizard-field :label="__('groups.phone_number')" for="phone">
                    @include('partials.inputs.phone-input', [
                        'name' => 'phone',
                        'value' => old('phone'),
                    ])
                </x-wizard-field>
                <x-wizard-field :label="__('groups.email_address')" for="email">
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="app-input">
                </x-wizard-field>
            </div>
        </div>

        <div class="app-card app-card-padded space-y-4">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600">{{ __('groups.group_leader') }}</h2>
            <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('groups.leader_hint') }}</p>
            <div class="wizard-form-grid wizard-form-grid-2">
                <div class="wizard-field sm:col-span-2">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="app-label">{{ __('applicants.first_name') }}</label>
                            <input type="text" value="{{ $applicant->first_name }}" class="app-input" readonly>
                        </div>
                        <div>
                            <label class="app-label">{{ __('applicants.middle_name') }}</label>
                            <input type="text" value="{{ $applicant->middle_name }}" class="app-input" readonly>
                        </div>
                        <div>
                            <label class="app-label">{{ __('applicants.last_name') }}</label>
                            <input type="text" value="{{ $applicant->last_name }}" class="app-input" readonly>
                        </div>
                    </div>
                </div>
                <x-wizard-field :label="__('applicants.nin')">
                    <input type="text" value="{{ $applicant->nin }}" class="app-input" readonly>
                </x-wizard-field>
                <x-wizard-field :label="__('common.phone')">
                    <input type="text" value="{{ $applicant->phone }}" class="app-input" readonly>
                </x-wizard-field>
                <x-wizard-field :label="__('common.email')">
                    <input type="text" value="{{ $applicant->email }}" class="app-input" readonly>
                </x-wizard-field>
                <x-wizard-field :label="__('applicants.marital_status')" for="leader_marital_status">
                    <input type="text" value="{{ $applicant->marital_status ? __('applicants.marital_statuses.'.$applicant->marital_status) : __('common.na') }}" class="app-input" readonly>
                </x-wizard-field>
                <x-wizard-field :label="__('applicants.dob')" for="leader_dob">
                    <input type="text" id="leader_dob" value="{{ $applicant->dob?->format('Y-m-d') }}" class="app-input" readonly>
                    <input type="hidden" name="leader[dob]" value="{{ old('leader.dob', $applicant->dob?->format('Y-m-d')) }}">
                </x-wizard-field>
                <x-wizard-field :label="__('applicants.sex')" for="leader_sex" :required="true">
                    @include('partials.inputs.female-sex-field', ['name' => 'leader[sex]', 'id' => 'leader_sex'])
                </x-wizard-field>
                @include('partials.inputs.leadership-role-select', [
                    'name' => 'leader[leadership_role]',
                    'value' => old('leader.leadership_role'),
                ])
            </div>
        </div>

        <div class="app-card app-card-padded space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600">{{ __('groups.additional_members') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">{{ __('groups.members_form_hint') }}</p>
                </div>
                <button type="button" data-group-add-member class="app-btn app-btn-primary">{{ __('groups.add_member') }}</button>
            </div>

            <div data-group-members-list class="space-y-4"></div>

            <template id="group-member-row-template">
                <div class="member-collapsible border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden">
                    <div class="member-collapsible__header">
                        <button type="button" class="member-collapsible__toggle" data-member-toggle aria-expanded="false">
                            <div class="min-w-0 text-left">
                                <p class="font-semibold text-slate-900 dark:text-white" data-member-title></p>
                                <p class="text-sm text-slate-500 dark:text-zinc-400 mt-0.5" data-member-summary hidden></p>
                            </div>
                            <svg class="collapsible-chevron shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <button type="button" data-member-remove class="text-sm text-red-600 hover:text-red-500 font-medium shrink-0">{{ __('groups.remove_member') }}</button>
                    </div>
                    <div data-member-body class="member-collapsible__body space-y-4" hidden>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
                                <input type="text" class="app-input" data-member-field="first_name" required>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.middle_name') }}</label>
                                <input type="text" class="app-input" data-member-field="middle_name">
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
                                <input type="text" class="app-input" data-member-field="last_name" required>
                            </div>
                        </div>
                        <div class="wizard-form-grid wizard-form-grid-2">
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.nin') }} @include('partials.required-mark')</label>
                                <input type="text" class="app-input app-nin-input" data-nin-input data-member-field="nin" required>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.dob') }} @include('partials.required-mark')</label>
                                <input type="date" class="app-input" data-member-dob data-member-field="dob" required>
                                <p class="mt-1.5 text-xs font-medium text-indigo-600" data-member-age hidden></p>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.sex') }} @include('partials.required-mark')</label>
                                <input type="hidden" data-member-field="sex" value="Female">
                                <input type="text" class="app-input bg-gray-100 cursor-not-allowed" value="{{ __('applicants.female') }}" readonly>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('common.phone') }} @include('partials.required-mark')</label>
                                <div class="app-phone-field" data-phone-field>
                                    <span class="app-phone-prefix" aria-hidden="true">
                                        <span class="app-phone-flag">@include('partials.flags.tanzania')</span>
                                        <span class="app-phone-code">+255</span>
                                    </span>
                                    <input type="tel" class="app-phone-local" data-phone-local inputmode="numeric" maxlength="9" required>
                                    <input type="hidden" data-phone-hidden data-member-field="phone">
                                </div>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.marital_status') }} @include('partials.required-mark')</label>
                                <select class="app-select" data-member-field="marital_status" required>
                                    <option value="">{{ __('applicants.select_marital_status') }}</option>
                                    @foreach(\App\Models\Applicant::MARITAL_STATUSES as $status)
                                        <option value="{{ $status }}">{{ __('applicants.marital_statuses.'.$status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('common.email') }}</label>
                                <input type="email" class="app-input" data-member-field="email">
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('groups.leadership') }}</label>
                                <select class="app-select" data-member-field="leadership_role">
                                    <option value="">{{ __('groups.select_leadership') }}</option>
                                    @foreach(\App\Support\GroupLeadershipRole::options() as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="app-card app-card-padded flex justify-end">
            <button type="submit" class="app-btn app-btn-success">{{ __('groups.save_group_once') }}</button>
        </div>
    </form>
</div>

<script type="application/json" id="group-setup-data">@json(['members' => $initialMembers, 'memberLabel' => __('groups.member_n')])</script>
@endsection
