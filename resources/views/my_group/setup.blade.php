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
                <div class="member-collapsible border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden">
                    <div class="member-collapsible__header">
                        <button
                            type="button"
                            class="member-collapsible__toggle"
                            @click="toggleMember(index)"
                            :aria-expanded="isMemberOpen(index)"
                        >
                            <div class="min-w-0 text-left">
                                <p class="font-semibold text-slate-900 dark:text-white" x-text="memberLabel(index)"></p>
                                <p class="text-sm text-slate-500 dark:text-zinc-400 mt-0.5" x-show="memberSummary(index)" x-text="memberSummary(index)"></p>
                            </div>
                            <svg class="collapsible-chevron shrink-0" :class="{ 'is-open': isMemberOpen(index) }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <button type="button" @click="removeMember(index)" class="text-sm text-red-600 hover:text-red-500 font-medium shrink-0" x-show="members.length > 1">{{ __('groups.remove_member') }}</button>
                    </div>
                    <div x-show="isMemberOpen(index)" x-cloak class="member-collapsible__body space-y-4">
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
                            <input type="date" class="app-input" x-model="member.dob" :name="'members[' + index + '][dob]'" :max="maxAdultDob" required>
                            <p class="mt-1.5 text-xs font-medium text-indigo-600" x-show="memberAgeLabel(member.dob)" x-text="memberAgeLabel(member.dob)"></p>
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
        openMembers: [],
        maxAdultDob: @json(now()->subYears(18)->toDateString()),
        ageTemplate: @json(__('applicants.age_years', ['age' => ':age'])),

        init() {
            if (!this.members.length) {
                this.addMember();
            } else {
                this.openMembers = [0];
            }

            this.$nextTick(() => window.initIdentityInputs?.(this.$root));
        },

        memberAgeLabel(dob) {
            const age = window.calculateAge?.(dob);
            if (age === null || age === undefined) {
                return '';
            }

            return this.ageTemplate.replace(':age', String(age));
        },

        memberLabel(index) {
            return payload.memberLabel.replace(':n', String(index + 1));
        },

        memberSummary(index) {
            const member = this.members[index];
            const name = [member.first_name, member.last_name].filter(Boolean).join(' ').trim();

            return name;
        },

        isMemberOpen(index) {
            return this.openMembers.includes(index);
        },

        toggleMember(index) {
            if (this.isMemberOpen(index)) {
                this.openMembers = this.openMembers.filter((item) => item !== index);

                return;
            }

            this.openMembers.push(index);
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

            this.openMembers = [this.members.length - 1];

            this.$nextTick(() => window.initIdentityInputs?.(this.$root));
        },

        removeMember(index) {
            this.members.splice(index, 1);
            this.openMembers = this.openMembers
                .filter((item) => item !== index)
                .map((item) => (item > index ? item - 1 : item));

            if (!this.openMembers.length && this.members.length) {
                this.openMembers = [Math.min(index, this.members.length - 1)];
            }
        },
    }));
});
</script>
@endsection
