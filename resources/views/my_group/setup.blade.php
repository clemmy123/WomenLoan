@extends('layouts.app')

@section('title', __('groups.setup_title'))

@section('content')
<div class="page page-medium" x-data="groupSetup()">
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('groups.setup_title') }}</h1>
            <p class="page-subtitle">{{ __('groups.setup_subtitle') }}</p>
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
                <button type="button" @click="addMember()" class="app-btn app-btn-primary">{{ __('groups.add_member') }}</button>
            </div>

            <template x-for="(member, index) in members" :key="index">
                <div class="border border-slate-200 dark:border-white/10 rounded-xl p-4 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-semibold text-slate-900 dark:text-white" x-text="memberLabel(index)"></p>
                        <button type="button" @click="removeMember(index)" class="text-sm text-red-600 hover:text-red-500 font-medium" x-show="members.length > 1">{{ __('groups.remove_member') }}</button>
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
                                <input type="text" class="app-input" x-model="member.first_name" :name="'members[' + index + '][first_name]'" required>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.middle_name') }}</label>
                                <input type="text" class="app-input" x-model="member.middle_name" :name="'members[' + index + '][middle_name]'">
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
                                <input type="text" class="app-input" x-model="member.last_name" :name="'members[' + index + '][last_name]'" required>
                            </div>
                        </div>
                        <div class="wizard-form-grid wizard-form-grid-2">
                        <div class="wizard-field">
                            <label class="app-label">{{ __('applicants.nin') }} @include('partials.required-mark')</label>
                            <input type="text" class="app-input app-nin-input" data-nin-input :name="'members[' + index + '][nin]'" :value="member.nin" required>
                        </div>
                        <div class="wizard-field">
                            <label class="app-label">{{ __('applicants.dob') }} @include('partials.required-mark')</label>
                            <input type="date" class="app-input" x-model="member.dob" :name="'members[' + index + '][dob]'" required>
                        </div>
                        <div class="wizard-field">
                            <label class="app-label">{{ __('applicants.sex') }} @include('partials.required-mark')</label>
                            <input type="hidden" :name="'members[' + index + '][sex]'" value="Female">
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
                                <input type="hidden" data-phone-hidden :name="'members[' + index + '][phone]'" :value="member.phone">
                            </div>
                        </div>
                        <div class="wizard-field">
                            <label class="app-label">{{ __('applicants.marital_status') }} @include('partials.required-mark')</label>
                            <select class="app-select" x-model="member.marital_status" :name="'members[' + index + '][marital_status]'" required>
                                <option value="">{{ __('applicants.select_marital_status') }}</option>
                                @foreach(\App\Models\Applicant::MARITAL_STATUSES as $status)
                                    <option value="{{ $status }}">{{ __('applicants.marital_statuses.'.$status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wizard-field">
                            <label class="app-label">{{ __('common.email') }}</label>
                            <input type="email" class="app-input" x-model="member.email" :name="'members[' + index + '][email]'">
                        </div>
                        <div class="wizard-field">
                            <label class="app-label">{{ __('groups.leadership') }}</label>
                            <select class="app-select" x-model="member.leadership_role" :name="'members[' + index + '][leadership_role]'">
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
<script>
document.addEventListener('alpine:init', () => {
    const payload = JSON.parse(document.getElementById('group-setup-data').textContent);

    Alpine.data('groupSetup', () => ({
        members: payload.members,

        init() {
            if (!this.members.length) {
                this.addMember();
            }

            this.$nextTick(() => window.initIdentityInputs?.(this.$root));
        },

        memberLabel(index) {
            return payload.memberLabel.replace(':n', String(index + 1));
        },

        addMember() {
            this.members.push({
                first_name: '',
                middle_name: '',
                last_name: '',
                nin: '',
                dob: '',
                phone: '',
                email: '',
                sex: '',
                marital_status: '',
                leadership_role: '',
            });

            this.$nextTick(() => window.initIdentityInputs?.(this.$root));
        },

        removeMember(index) {
            this.members.splice(index, 1);
        },
    }));
});
</script>
@endsection
