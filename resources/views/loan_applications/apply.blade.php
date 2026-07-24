@extends('layouts.app')

@section('title', ($editing ?? false) ? __('loans.edit_title') : __('loans.wizard_title'))

@section('content')
@php
    $fd = fn (string $key, mixed $default = '') => old($key, $formData[$key] ?? $default);
    $wizardSteps = [
        ['icon' => 'business', 'title' => __('loans.wizard_steps.1')],
        ['icon' => 'guarantor', 'title' => __('loans.wizard_steps.2')],
        ['icon' => 'amount', 'title' => __('loans.wizard_steps.3')],
        ['icon' => 'bank', 'title' => __('loans.wizard_steps.4')],
        ['icon' => 'declaration', 'title' => __('loans.wizard_steps.5')],
        ['icon' => 'review', 'title' => __('loans.wizard_steps.6')],
    ];
    $wizardTotalSteps = 6;
@endphp
<script type="application/json" id="loan-wizard-config">@json($wizardConfig)</script>

<div x-data="loanWizard()" class="page page-medium">
    <div class="app-card app-card-padded">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ ($editing ?? false) ? __('loans.edit_title') : __('loans.apply_title') }}</h2>
                <p class="page-subtitle mt-1">{{ __('dashboard.track_id') }}: <span class="font-mono">{{ $trackId }}</span></p>
            </div>
            @if($isDraft ?? false)
                @include('partials.badge', ['variant' => 'warning', 'text' => __('loans.draft_status')])
            @endif
        </div>

        @if($isDraft ?? false)
            <p class="text-sm text-slate-600 dark:text-zinc-400 mt-3">{{ __('loans.draft_status_hint', ['step' => $wizardStep ?? 1, 'total' => $wizardTotalSteps]) }}</p>
        @endif

        @if(! empty($validationStepHint))
            @include('partials.status-card', [
                'type' => 'error',
                'message' => $validationStepHint['message'],
                'class' => 'mt-4',
            ])
        @endif

        <x-wizard-stepper :steps="$wizardSteps" class="mt-6" />
    </div>

    <form action="{{ ($editing ?? false) ? route('loan-applications.update', $editingLoan) : route('loan-applications.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 wizard-form" novalidate @submit="prepareSubmit($event)">
        @csrf
        @if($editing ?? false)
            @method('PUT')
        @else
            <input type="hidden" name="track_id" value="{{ $trackId }}">
        @endif
        <input type="hidden" name="step" value="{{ (int) ($wizardStep ?? 1) }}" x-bind:value="step">

        @php
            $profileLoanType = old('loan_type', $formData['loan_type'] ?? $applicant?->preferred_loan_type ?? ($editingLoan?->loan_type ?? ''));
        @endphp
        <input type="hidden" name="loan_type" value="{{ $profileLoanType }}" x-model="loanType">
        @if(($userGroup ?? null) && $profileLoanType === 'group')
            <input type="hidden" name="loan_group_id" value="{{ $userGroup->id }}">
        @endif

        @if($profileLoanType === 'individual')
            <div class="app-card app-card-padded border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50/50 dark:bg-indigo-500/5">
                <p class="font-semibold text-indigo-900 dark:text-indigo-200">{{ __('loans.continue_as_individual') }}</p>
                <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">{{ __('loans.continue_as_individual_hint') }}</p>
            </div>
        @elseif($profileLoanType === 'group')
            <div class="app-card app-card-padded border border-violet-200 dark:border-violet-500/30 bg-violet-50/50 dark:bg-violet-500/5">
                <p class="font-semibold text-violet-900 dark:text-violet-200">{{ __('loans.continue_as_group') }}</p>
                @if($canSetupGroup ?? false)
                    <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">{{ __('loans.continue_as_group_hint') }}</p>
                @endif
                @if($userGroup ?? null)
                    <div class="rounded-xl border border-slate-200 dark:border-white/10 p-4 mt-4 mb-4">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $userGroup->name }}</p>
                        @if($userGroup->registration_number)
                            <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('groups.reg_number') }}: {{ $userGroup->registration_number }}</p>
                        @endif
                    </div>
                    <div x-data="{ membersOpen: false }" class="rounded-xl border border-slate-200 dark:border-white/10 overflow-hidden">
                        <button
                            type="button"
                            class="collapsible-toggle"
                            @click="membersOpen = !membersOpen"
                            :aria-expanded="membersOpen"
                        >
                            <div class="text-left">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ __('groups.group_members') }}</p>
                                <p class="text-sm text-slate-500 dark:text-zinc-400 mt-0.5">{{ __('groups.members_count', ['count' => $userGroup->members->count()]) }}</p>
                            </div>
                            <span class="collapsible-toggle__action">
                                <span x-text="membersOpen ? @js(__('groups.hide_members_list')) : @js(__('groups.show_members_list', ['count' => $userGroup->members->count()]))"></span>
                                <svg class="collapsible-chevron" :class="{ 'is-open': membersOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </button>
                        <div x-show="membersOpen" x-cloak class="collapsible-panel">
                            <div class="overflow-x-auto">
                                <table class="app-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('applicants.first_name') }}</th>
                                            <th>{{ __('applicants.middle_name') }}</th>
                                            <th>{{ __('applicants.last_name') }}</th>
                                            <th>{{ __('applicants.nin') }}</th>
                                            <th>{{ __('applicants.dob') }}</th>
                                            <th>{{ __('applicants.sex') }}</th>
                                            <th>{{ __('common.phone') }}</th>
                                            <th>{{ __('common.email') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($userGroup->members as $member)
                                        <tr>
                                            <td>{{ $member->first_name }}@if($member->is_group_leader) <span class="text-xs text-indigo-600">({{ __('groups.group_leader') }})</span>@endif</td>
                                            <td>{{ $member->middle_name ?: '—' }}</td>
                                            <td>{{ $member->last_name }}</td>
                                            <td class="font-mono text-sm">{{ $member->nin }}</td>
                                            <td>{{ $member->dob?->translatedFormat('d M Y') ?? '—' }}</td>
                                            <td>{{ $member->sex ?? '—' }}</td>
                                            <td>{{ $member->phone }}</td>
                                            <td>{{ $member->email ?? '—' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @elseif($canSetupGroup ?? false)
                    <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 p-4 mt-4 text-sm text-amber-900 dark:text-amber-200">
                        <p>{{ __('loans.group_setup_required') }}</p>
                        <a href="{{ route('my-group.create') }}" class="inline-block mt-3 font-semibold text-indigo-600 hover:text-indigo-500">{{ __('groups.setup_title') }} →</a>
                    </div>
                @else
                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-4">{{ __('loans.group_not_available') }}</p>
                @endif
            </div>
        @endif

        <div data-wizard-step="1" class="app-card app-card-padded wizard-panel" :class="step === 1 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('loans.wizard_steps.1') }}</h3>

            <div class="wizard-section">
                <p class="wizard-section-title">{{ __('loans.business_location') }}</p>
                <div class="wizard-form-grid wizard-form-grid-2">
                    <x-wizard-field :label="__('geo.region')" for="region_id" :required="true">
                        <select name="region_id" id="region_id" x-model="selectedRegion"
                            @change="selectedDistrict=''; selectedCouncil=''; selectedWard=''; selectedStreet=''; districts=[]; councils=[]; wards=[]; streets=[]; loadDistricts(selectedRegion);"
                            :required="step === 1" class="app-select">
                            <option value="">-- {{ __('geo.select_region') }} --</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" @selected((string) $fd('region_id') === (string) $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.district')" for="district_id" :required="true">
                        <select name="district_id" id="district_id" x-model="selectedDistrict"
                            @change="selectedCouncil=''; selectedWard=''; selectedStreet=''; councils=[]; wards=[]; streets=[]; loadCouncils(selectedDistrict);"
                            :disabled="!districts.length || loading" :required="step === 1" class="app-select">
                            <option value="">-- {{ __('geo.select_district') }} --</option>
                            <template x-for="district in districts" :key="district.id">
                                <option :value="String(district.id)" x-text="district.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.council')" for="council_id" :required="true">
                        <select name="council_id" id="council_id" x-model="selectedCouncil"
                            @change="selectedWard=''; selectedStreet=''; wards=[]; streets=[]; loadWards(selectedCouncil);"
                            :disabled="!councils.length || loading" :required="step === 1" class="app-select">
                            <option value="">-- {{ __('geo.select_council') }} --</option>
                            <template x-for="council in councils" :key="council.id">
                                <option :value="String(council.id)" x-text="council.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.ward')" for="ward_id" :required="true">
                        <select name="ward_id" id="ward_id" x-model="selectedWard"
                            @change="selectedStreet=''; streets=[]; loadStreets(selectedWard);"
                            :disabled="!wards.length || loading" :required="step === 1" class="app-select">
                            <option value="">-- {{ __('geo.select_ward') }} --</option>
                            <template x-for="ward in wards" :key="ward.id">
                                <option :value="String(ward.id)" x-text="ward.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.street')" for="street_id" :required="true" class="wizard-form-grid-span-2">
                        <select name="street_id" id="street_id" x-model="selectedStreet"
                            :disabled="!streets.length || loading" :required="step === 1" class="app-select">
                            <option value="">-- {{ __('geo.select_street') }} --</option>
                            <template x-for="street in streets" :key="street.id">
                                <option :value="String(street.id)" x-text="street.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>
                </div>
            </div>

            <div class="wizard-section">
                <p class="wizard-section-title">{{ __('loans.business_details') }}</p>
                <div class="wizard-form-grid wizard-form-grid-2">
                    <x-wizard-field :label="__('loans.business_name')" for="business_name" :required="true">
                        <input type="text" name="business_name" id="business_name" value="{{ $fd('business_name') }}" :required="step === 1" class="app-input">
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_phone')" for="business_phone" :required="true">
                        @include('partials.inputs.phone-input', [
                            'name' => 'business_phone',
                            'value' => $fd('business_phone', $applicant?->phone),
                            'required' => true,
                        ])
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_email')" for="business_email" :required="true">
                        <input type="email" name="business_email" id="business_email" value="{{ $fd('business_email', $applicant?->email) }}" :required="step === 1" class="app-input">
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_sector')" for="business_sector" :required="true">
                        <select
                            name="business_sector"
                            id="business_sector"
                            x-model="selectedBusinessSector"
                            @change="onBusinessSectorChange()"
                            :required="step === 1"
                            class="app-select"
                        >
                            <option value="">{{ __('loans.select_business_sector') }}</option>
                            @foreach($businessSectors as $sector)
                                <option value="{{ $sector->name }}" @selected($fd('business_sector') === $sector->name)>{{ $sector->name }}</option>
                            @endforeach
                        </select>
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_type')" for="business_type" :required="true">
                        <select
                            name="business_type"
                            id="business_type"
                            x-model="selectedBusinessType"
                            :required="step === 1"
                            :disabled="!selectedBusinessSector"
                            class="app-select"
                        >
                            <option value="">{{ __('loans.select_business_type') }}</option>
                            <template x-for="type in filteredBusinessTypes" :key="type.id">
                                <option :value="type.name" x-text="type.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.tin_number')" for="tin_number" :required="true" class="wizard-form-grid-span-2">
                        <input type="text" name="tin_number" id="tin_number" value="{{ $fd('tin_number') }}" :required="step === 1" class="app-input">
                    </x-wizard-field>
                </div>
            </div>

            <div class="wizard-section">
                <p class="wizard-section-title">{{ __('loans.supporting_documents') }}</p>
                <div class="wizard-form-grid doc-attachments-grid">
                    <x-document-upload
                        name="business_proposal_document"
                        :title="__('loans.business_proposal')"
                        :required="!($editing ?? false)"
                        :existing="($editing ?? false) ? ($editingLoan->businessDetails?->business_proposal_document) : null"
                    >
                        <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 1" @endif</x-slot:inputAttributes>
                    </x-document-upload>

                    <x-document-upload
                        name="business_registration_attachment"
                        :title="__('loans.business_registration')"
                        :required="!($editing ?? false)"
                        :existing="($editing ?? false) ? ($editingLoan->businessDetails?->business_registration_attachment) : null"
                    >
                        <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 1" @endif</x-slot:inputAttributes>
                    </x-document-upload>

                    <x-document-upload
                        name="proof_address_attachment"
                        :title="__('loans.proof_address')"
                        :required="!($editing ?? false)"
                        :existing="($editing ?? false) ? ($editingLoan->businessDetails?->proof_address_attachment) : null"
                    >
                        <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 1" @endif</x-slot:inputAttributes>
                    </x-document-upload>

                    <div data-loan-scope="group" x-show="loanType === 'group'" x-cloak class="wizard-form-grid wizard-form-grid-2 wizard-form-grid-span-2 doc-attachments-grid">
                        <x-document-upload
                            name="group_constitution"
                            :title="__('loans.group_constitution')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->group_constitution) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 1 && loanType === 'group'" @endif</x-slot:inputAttributes>
                        </x-document-upload>

                        <x-document-upload
                            name="group_muhtasari"
                            :title="__('loans.group_muhtasari')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->group_muhtasari) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 1 && loanType === 'group'" @endif</x-slot:inputAttributes>
                        </x-document-upload>

                        <x-document-upload
                            name="group_certificate"
                            :title="__('loans.group_certificate')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->group_certificate) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 1 && loanType === 'group'" @endif</x-slot:inputAttributes>
                        </x-document-upload>
                    </div>

                    <div data-loan-scope="shared" class="wizard-form-grid wizard-form-grid-2 wizard-form-grid-span-2 doc-attachments-grid">
                        <x-document-upload
                            name="application_letter"
                            :title="__('loans.application_letter')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->application_letter) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 1" @endif</x-slot:inputAttributes>
                        </x-document-upload>

                        <x-document-upload
                            name="bank_statement"
                            :title="__('loans.bank_statement')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->bank_statement) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 1" @endif</x-slot:inputAttributes>
                        </x-document-upload>
                    </div>
                </div>
            </div>
        </div>

        <div data-wizard-step="2" class="app-card app-card-padded wizard-panel" :class="step === 2 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('loans.wizard_steps.2') }}</h3>
            <div class="wizard-form-grid wizard-form-grid-2">
                <div class="wizard-field wizard-form-grid-span-2">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-wizard-field :label="__('applicants.first_name')" for="guarantor_first_name" :required="true">
                            <input type="text" name="guarantor_first_name" id="guarantor_first_name" value="{{ $fd('guarantor_first_name') }}" :required="step === 2" class="app-input">
                        </x-wizard-field>
                        <x-wizard-field :label="__('applicants.middle_name')" for="guarantor_middle_name">
                            <input type="text" name="guarantor_middle_name" id="guarantor_middle_name" value="{{ $fd('guarantor_middle_name') }}" class="app-input">
                        </x-wizard-field>
                        <x-wizard-field :label="__('applicants.last_name')" for="guarantor_last_name" :required="true">
                            <input type="text" name="guarantor_last_name" id="guarantor_last_name" value="{{ $fd('guarantor_last_name') }}" :required="step === 2" class="app-input">
                        </x-wizard-field>
                    </div>
                </div>
                <x-wizard-field :label="__('loans.guarantor_phone')" for="guarantor_phone" :required="true">
                    @include('partials.inputs.phone-input', [
                        'name' => 'guarantor_phone',
                        'value' => $fd('guarantor_phone'),
                        'required' => true,
                    ])
                </x-wizard-field>
                <x-wizard-field :label="__('loans.guarantor_relationship')" for="guarantor_relationship">
                    <select name="guarantor_relationship" id="guarantor_relationship" class="app-select">
                        <option value="">{{ __('loans.select_relationship') }}</option>
                        @foreach(__('loans.guarantor_relationships') as $value => $label)
                            <option value="{{ $value }}" @selected($fd('guarantor_relationship') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-wizard-field>
                <x-wizard-field :label="__('loans.guarantor_occupation')" for="guarantor_occupation">
                    <input type="text" name="guarantor_occupation" id="guarantor_occupation" value="{{ $fd('guarantor_occupation') }}" class="app-input">
                </x-wizard-field>
                <x-wizard-field :label="__('loans.guarantor_sex')" for="guarantor_sex" :required="true">
                    @include('partials.inputs.sex-select', [
                        'name' => 'guarantor_sex',
                        'value' => $fd('guarantor_sex'),
                        'required' => true,
                    ])
                </x-wizard-field>
                <x-wizard-field :label="__('loans.guarantor_nin')" for="guarantor_nin" :required="true" class="wizard-form-grid-span-2">
                    @include('partials.inputs.nin-input', [
                        'name' => 'guarantor_nin',
                        'value' => $fd('guarantor_nin'),
                        'class' => 'w-full app-input',
                        'required' => true,
                    ])
                </x-wizard-field>
            </div>

            <div class="wizard-section mt-6">
                <p class="wizard-section-title">{{ __('loans.guarantor_location') }}</p>
                <div class="wizard-form-grid wizard-form-grid-2">
                    <x-wizard-field :label="__('geo.region')" for="guarantor_region_id" :required="true">
                        <select name="guarantor_region_id" id="guarantor_region_id" x-model="guarantorRegion"
                            @change="guarantorDistrict=''; guarantorCouncil=''; guarantorWard=''; guarantorStreet=''; guarantorDistricts=[]; guarantorCouncils=[]; guarantorWards=[]; guarantorStreets=[]; loadGuarantorDistricts(guarantorRegion);"
                            :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_region') }} --</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" @selected((string) $fd('guarantor_region_id') === (string) $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.district')" for="guarantor_district_id" :required="true">
                        <select name="guarantor_district_id" id="guarantor_district_id" x-model="guarantorDistrict"
                            @change="guarantorCouncil=''; guarantorWard=''; guarantorStreet=''; guarantorCouncils=[]; guarantorWards=[]; guarantorStreets=[]; loadGuarantorCouncils(guarantorDistrict);"
                            :disabled="!guarantorDistricts.length || guarantorLoading" :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_district') }} --</option>
                            <template x-for="district in guarantorDistricts" :key="district.id">
                                <option :value="String(district.id)" x-text="district.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.council')" for="guarantor_council_id" :required="true">
                        <select name="guarantor_council_id" id="guarantor_council_id" x-model="guarantorCouncil"
                            @change="guarantorWard=''; guarantorStreet=''; guarantorWards=[]; guarantorStreets=[]; loadGuarantorWards(guarantorCouncil);"
                            :disabled="!guarantorCouncils.length || guarantorLoading" :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_council') }} --</option>
                            <template x-for="council in guarantorCouncils" :key="council.id">
                                <option :value="String(council.id)" x-text="council.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.ward')" for="guarantor_ward_id" :required="true">
                        <select name="guarantor_ward_id" id="guarantor_ward_id" x-model="guarantorWard"
                            @change="guarantorStreet=''; guarantorStreets=[]; loadGuarantorStreets(guarantorWard);"
                            :disabled="!guarantorWards.length || guarantorLoading" :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_ward') }} --</option>
                            <template x-for="ward in guarantorWards" :key="ward.id">
                                <option :value="String(ward.id)" x-text="ward.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.street')" for="guarantor_street_id" :required="true" class="wizard-form-grid-span-2">
                        <select name="guarantor_street_id" id="guarantor_street_id" x-model="guarantorStreet"
                            :disabled="!guarantorStreets.length || guarantorLoading" :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_street') }} --</option>
                            <template x-for="street in guarantorStreets" :key="street.id">
                                <option :value="String(street.id)" x-text="street.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>
                </div>
            </div>

            <div class="wizard-form-grid wizard-form-grid-2 mt-6">
                <x-document-upload
                    name="guarantor_letter"
                    :title="__('loans.guarantor_letter')"
                    class="wizard-form-grid-span-2"
                    :required="!($editing ?? false)"
                    :existing="($editing ?? false) ? ($formData['guarantor_letter_existing'] ?? null) : null"
                >
                    <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2" @endif</x-slot:inputAttributes>
                </x-document-upload>
            </div>
        </div>

        <div data-wizard-step="3" class="app-card app-card-padded wizard-panel" :class="step === 3 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('loans.wizard_steps.3') }}</h3>
            <x-wizard-field :label="__('loans.requested_amount')" for="requested_amount" :required="true">
                @include('partials.inputs.amount-input', [
                    'name' => 'requested_amount',
                    'value' => $fd('requested_amount'),
                    'inputAttributes' => ':required="step === 3"',
                ])
            </x-wizard-field>
        </div>

        <div data-wizard-step="4" class="app-card app-card-padded wizard-panel" :class="step === 4 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('loans.wizard_steps.4') }}</h3>
            <div class="wizard-form-grid wizard-form-grid-2">
                <x-wizard-field :label="__('loans.bank_name')" for="bank_name">
                    @php $selectedBank = $fd('bank_name'); @endphp
                    <select name="bank_name" id="bank_name" class="app-select">
                        <option value="">{{ __('loans.select_bank') }}</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank }}" @selected($selectedBank === $bank)>{{ $bank }}</option>
                        @endforeach
                        @if($selectedBank && ! in_array($selectedBank, $banks, true))
                            <option value="{{ $selectedBank }}" selected>{{ $selectedBank }}</option>
                        @endif
                    </select>
                </x-wizard-field>
                <x-wizard-field :label="__('loans.bank_number')" for="bank_number">
                    <input type="text" name="bank_number" id="bank_number" value="{{ $fd('bank_number') }}" class="app-input">
                </x-wizard-field>
            </div>
        </div>

        <div data-wizard-step="5" class="app-card app-card-padded wizard-panel" :class="step === 5 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('loans.wizard_steps.5') }}</h3>
            <label class="flex items-start gap-3 cursor-pointer text-slate-900 dark:text-white">
                <input
                    type="checkbox"
                    name="declaration"
                    value="1"
                    required
                    x-model="declarationAccepted"
                    class="mt-1 w-5 h-5 accent-indigo-600"
                >
                <span>{{ __('loans.confirm_accuracy') }} <span class="text-red-600" aria-hidden="true">*</span></span>
            </label>
            <p x-show="!declarationAccepted" x-cloak class="mt-3 text-sm text-amber-700 dark:text-amber-300">{{ __('loans.declaration_required_hint') }}</p>
        </div>

        <div data-wizard-step="6" class="app-card app-card-padded wizard-panel" :class="step === 6 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">{{ __('loans.wizard_steps.6') }}</h3>
            @include('loan_applications._wizard_preview')

            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-white/10">
                <p class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ __('loans.preview_choose_action') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if(!($editing ?? false))
                    <div class="rounded-xl border border-slate-200 dark:border-white/10 bg-slate-50/80 dark:bg-white/5 p-5 flex flex-col gap-3">
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white">{{ __('loans.preview_save_title') }}</h4>
                            <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">{{ __('loans.preview_save_hint') }}</p>
                        </div>
                        <button type="submit" name="form_action" value="save_draft" formnovalidate @click="prepareDraftSubmit()" class="app-btn app-btn-secondary mt-auto">{{ __('loans.save_draft') }}</button>
                    </div>
                    @elseif(($canSubmitToWard ?? false))
                    <div class="rounded-xl border border-slate-200 dark:border-white/10 bg-slate-50/80 dark:bg-white/5 p-5 flex flex-col gap-3">
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white">{{ __('loans.preview_save_changes_title') }}</h4>
                            <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">{{ __('loans.preview_save_changes_hint') }}</p>
                        </div>
                        <button type="submit" class="app-btn app-btn-secondary mt-auto">{{ __('loans.update_application') }}</button>
                    </div>
                    @endif
                    @if($canSubmitToWard ?? true)
                    <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-500/5 p-5 flex flex-col gap-3 {{ ($editing ?? false) && !($canSubmitToWard ?? false) ? 'md:col-span-2' : '' }}">
                        <div>
                            <h4 class="font-bold text-emerald-900 dark:text-emerald-200">{{ __('loans.preview_submit_title') }}</h4>
                            <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">{{ __('loans.preview_submit_hint') }}</p>
                        </div>
                        <button
                            type="button"
                            @click="openSubmitConfirm()"
                            :disabled="!declarationAccepted"
                            class="app-btn app-btn-success mt-auto"
                            :class="{ 'app-btn--faint': !declarationAccepted }"
                        >{{ __('loans.submit_application') }}</button>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="app-card app-card-padded flex flex-wrap justify-between items-center gap-3">
            <button type="button" x-show="step > 1" x-cloak @click="prevStep()" class="app-btn app-btn-secondary">{{ __('common.back') }}</button>
            <div class="flex flex-wrap gap-3 ml-auto">
                @if(!($editing ?? false))
                <button type="submit" name="form_action" value="save_draft" formnovalidate @click="prepareDraftSubmit()" x-show="step < totalSteps" x-cloak class="app-btn app-btn-ghost">{{ __('loans.save_draft') }}</button>
                @endif
                <button
                    type="button"
                    x-show="step < totalSteps"
                    x-cloak
                    @click="nextStep()"
                    :disabled="step === 5 && !declarationAccepted"
                    class="app-btn app-btn-primary"
                    :class="{ 'app-btn--faint': step === 5 && !declarationAccepted }"
                >{{ __('common.next') }}</button>
                <button type="submit" x-ref="finalSubmit" class="hidden" tabindex="-1" aria-hidden="true"></button>
            </div>
        </div>
    </form>

    @include('partials.confirm-modal', [
        'show' => 'submitModal',
        'close' => 'submitModal = false',
        'title' => __('loans.submit_confirm_title'),
        'message' => __('loans.submit_confirm_message'),
        'note' => __('loans.submit_confirm_warning'),
        'confirmLabel' => __('loans.submit_confirm_yes'),
        'confirmClick' => 'confirmSubmit()',
        'confirmVariant' => 'success',
    ])
</div>
@endsection
