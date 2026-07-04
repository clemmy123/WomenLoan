@extends('layouts.app')

@section('title', ($editing ?? false) ? __('loans.edit_title') : __('loans.wizard_title'))

@section('content')
@php
    $fd = fn (string $key, mixed $default = '') => old($key, $formData[$key] ?? $default);
@endphp
<script type="application/json" id="loan-wizard-config">@json($wizardConfig)</script>

<div x-data="loanWizard()" class="page page-medium">
    <div class="app-card app-card-padded">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ ($editing ?? false) ? __('loans.edit_title') : __('loans.apply_title') }}</h2>
        <p class="page-subtitle mt-1">{{ __('dashboard.track_id') }}: <span class="font-mono">{{ $trackId }}</span></p>
        <div class="flex items-center gap-2 mt-4">
            <template x-for="i in totalSteps" :key="i">
                <div class="h-2 flex-1 rounded-full transition-all duration-300"
                     :class="step >= i ? 'app-step-active' : 'bg-slate-200 dark:bg-white/10'"></div>
            </template>
        </div>
        <p class="text-xs uppercase font-bold text-slate-400 mt-2" x-text="stepText"></p>
    </div>

    <form action="{{ ($editing ?? false) ? route('loan-applications.update', $editingLoan) : route('loan-applications.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 wizard-form" novalidate @submit="prepareSubmit($event)">
        @csrf
        @if($editing ?? false)
            @method('PUT')
        @else
            <input type="hidden" name="track_id" value="{{ $trackId }}">
        @endif
        <input type="hidden" name="step" :value="step">

        <div data-wizard-step="1" class="app-card app-card-padded wizard-panel" :class="step === 1 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">1. {{ __('loans.wizard_steps.1') }}</h3>
            <x-wizard-field :label="__('loans.select_loan_type')" for="loan_type" :required="true">
                <select name="loan_type" id="loan_type" x-model="loanType" :required="step === 1" class="app-select">
                    <option value="">-- {{ __('loans.select_loan_type') }} --</option>
                    <option value="individual" @selected($fd('loan_type') === 'individual')>{{ __('loans.types.individual') }}</option>
                    <option value="group" @selected($fd('loan_type') === 'group')>{{ __('loans.types.group') }}</option>
                </select>
            </x-wizard-field>

            <div x-show="loanType === 'group'" x-cloak class="wizard-section mt-6">
                <p class="wizard-section-title">{{ __('loans.select_group') }}</p>
                @if($userGroup ?? null)
                    <input type="hidden" name="loan_group_id" value="{{ $userGroup->id }}">
                    <div class="rounded-xl border border-slate-200 dark:border-white/10 p-4 mb-4">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $userGroup->name }}</p>
                        @if($userGroup->registration_number)
                            <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('groups.reg_number') }}: {{ $userGroup->registration_number }}</p>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('common.full_name') }}</th>
                                    <th>{{ __('applicants.nin') }}</th>
                                    <th>{{ __('groups.member_age') }}</th>
                                    <th>{{ __('applicants.sex') }}</th>
                                    <th>{{ __('common.phone') }}</th>
                                    <th>{{ __('common.email') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userGroup->members as $member)
                                <tr>
                                    <td>{{ $member->full_name }}@if($member->is_group_leader) <span class="text-xs text-indigo-600">({{ __('groups.group_leader') }})</span>@endif</td>
                                    <td class="font-mono text-sm">{{ $member->nin }}</td>
                                    <td>{{ $member->age ?? '—' }}</td>
                                    <td>{{ $member->sex ?? '—' }}</td>
                                    <td>{{ $member->phone }}</td>
                                    <td>{{ $member->email ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($canSetupGroup ?? false)
                    <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 p-4 text-sm text-amber-900 dark:text-amber-200">
                        <p>{{ __('loans.group_setup_required') }}</p>
                        <a href="{{ route('my-group.create') }}" class="inline-block mt-3 font-semibold text-indigo-600 hover:text-indigo-500">{{ __('groups.setup_title') }} →</a>
                    </div>
                @else
                    <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('loans.group_not_available') }}</p>
                @endif
            </div>
        </div>

        <div data-wizard-step="2" class="app-card app-card-padded wizard-panel" :class="step === 2 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">2. {{ __('loans.wizard_steps.2') }}</h3>

            <div class="wizard-section">
                <p class="wizard-section-title">{{ __('loans.business_location') }}</p>
                <div class="wizard-form-grid wizard-form-grid-2">
                    <x-wizard-field :label="__('geo.region')" for="region_id" :required="true">
                        <select name="region_id" id="region_id" x-model="selectedRegion"
                            @change="selectedDistrict=''; selectedCouncil=''; selectedWard=''; selectedStreet=''; districts=[]; councils=[]; wards=[]; streets=[]; loadDistricts(selectedRegion);"
                            :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_region') }} --</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" @selected((string) $fd('region_id') === (string) $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.district')" for="district_id" :required="true">
                        <select name="district_id" id="district_id" x-model="selectedDistrict"
                            @change="selectedCouncil=''; selectedWard=''; selectedStreet=''; councils=[]; wards=[]; streets=[]; loadCouncils(selectedDistrict);"
                            :disabled="!districts.length || loading" :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_district') }} --</option>
                            <template x-for="district in districts" :key="district.id">
                                <option :value="String(district.id)" x-text="district.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.council')" for="council_id" :required="true">
                        <select name="council_id" id="council_id" x-model="selectedCouncil"
                            @change="selectedWard=''; selectedStreet=''; wards=[]; streets=[]; loadWards(selectedCouncil);"
                            :disabled="!councils.length || loading" :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_council') }} --</option>
                            <template x-for="council in councils" :key="council.id">
                                <option :value="String(council.id)" x-text="council.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.ward')" for="ward_id" :required="true">
                        <select name="ward_id" id="ward_id" x-model="selectedWard"
                            @change="selectedStreet=''; streets=[]; loadStreets(selectedWard);"
                            :disabled="!wards.length || loading" :required="step === 2" class="app-select">
                            <option value="">-- {{ __('geo.select_ward') }} --</option>
                            <template x-for="ward in wards" :key="ward.id">
                                <option :value="String(ward.id)" x-text="ward.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.street')" for="street_id" :required="true" class="wizard-form-grid-span-2">
                        <select name="street_id" id="street_id" x-model="selectedStreet"
                            :disabled="!streets.length || loading" :required="step === 2" class="app-select">
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
                        <input type="text" name="business_name" id="business_name" value="{{ $fd('business_name') }}" :required="step === 2" class="app-input">
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_phone')" for="business_phone" :required="true">
                        @include('partials.inputs.phone-input', [
                            'name' => 'business_phone',
                            'value' => $fd('business_phone', $applicant?->phone),
                            'required' => true,
                        ])
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_email')" for="business_email" :required="true">
                        <input type="email" name="business_email" id="business_email" value="{{ $fd('business_email', $applicant?->email) }}" :required="step === 2" class="app-input">
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_sector')" for="business_sector" :required="true">
                        <select
                            name="business_sector"
                            id="business_sector"
                            x-model="selectedBusinessSector"
                            @change="onBusinessSectorChange()"
                            :required="step === 2"
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
                            :required="step === 2"
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
                        <input type="text" name="tin_number" id="tin_number" value="{{ $fd('tin_number') }}" :required="step === 2" class="app-input">
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
                        <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2" @endif</x-slot:inputAttributes>
                    </x-document-upload>

                    <x-document-upload
                        name="business_registration_attachment"
                        :title="__('loans.business_registration')"
                        :required="!($editing ?? false)"
                        :existing="($editing ?? false) ? ($editingLoan->businessDetails?->business_registration_attachment) : null"
                    >
                        <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2" @endif</x-slot:inputAttributes>
                    </x-document-upload>

                    <x-document-upload
                        name="proof_address_attachment"
                        :title="__('loans.proof_address')"
                        :required="!($editing ?? false)"
                        :existing="($editing ?? false) ? ($editingLoan->businessDetails?->proof_address_attachment) : null"
                    >
                        <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2" @endif</x-slot:inputAttributes>
                    </x-document-upload>

                    <div data-loan-scope="group" x-show="loanType === 'group'" x-cloak class="wizard-form-grid wizard-form-grid-2 wizard-form-grid-span-2 doc-attachments-grid">
                        <x-document-upload
                            name="group_constitution"
                            :title="__('loans.group_constitution')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->group_constitution) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2 && loanType === 'group'" @endif</x-slot:inputAttributes>
                        </x-document-upload>

                        <x-document-upload
                            name="group_muhtasari"
                            :title="__('loans.group_muhtasari')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->group_muhtasari) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2 && loanType === 'group'" @endif</x-slot:inputAttributes>
                        </x-document-upload>

                        <x-document-upload
                            name="group_certificate"
                            :title="__('loans.group_certificate')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->group_certificate) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2 && loanType === 'group'" @endif</x-slot:inputAttributes>
                        </x-document-upload>
                    </div>

                    <div data-loan-scope="shared" class="wizard-form-grid wizard-form-grid-2 wizard-form-grid-span-2 doc-attachments-grid">
                        <x-document-upload
                            name="application_letter"
                            :title="__('loans.application_letter')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->application_letter) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2" @endif</x-slot:inputAttributes>
                        </x-document-upload>

                        <x-document-upload
                            name="bank_statement"
                            :title="__('loans.bank_statement')"
                            :required="!($editing ?? false)"
                            :existing="($editing ?? false) ? ($editingLoan->businessDetails?->bank_statement) : null"
                        >
                            <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 2" @endif</x-slot:inputAttributes>
                        </x-document-upload>
                    </div>
                </div>
            </div>
        </div>

        <div data-wizard-step="3" class="app-card app-card-padded wizard-panel" :class="step === 3 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">3. {{ __('loans.wizard_steps.3') }}</h3>
            <div class="wizard-form-grid wizard-form-grid-2">
                <x-wizard-field :label="__('loans.applicant_name')" for="applicant_name">
                    <input type="text" name="applicant_name" id="applicant_name" value="{{ $fd('applicant_name', $applicant?->full_name) }}" class="app-input">
                </x-wizard-field>
                <x-wizard-field :label="__('loans.applicant_phone')" for="applicant_phone">
                    <input type="text" name="applicant_phone" id="applicant_phone" value="{{ $fd('applicant_phone', $applicant?->phone) }}" class="app-input">
                </x-wizard-field>
                <x-wizard-field :label="__('loans.applicant_nin')" for="applicant_nin" class="wizard-form-grid-span-2">
                    <input type="text" name="applicant_nin" id="applicant_nin" value="{{ $fd('applicant_nin', $applicant?->nin) }}" class="app-input" readonly>
                </x-wizard-field>
                <x-wizard-field :label="__('loans.has_disability')" for="has_disability" :required="true">
                    <select name="has_disability" id="has_disability" :required="step === 3" class="app-select">
                        <option value="">{{ __('loans.select_yes_no') }}</option>
                        <option value="1" @selected((string) $fd('has_disability') === '1')>{{ __('common.yes') }}</option>
                        <option value="0" @selected((string) $fd('has_disability') === '0')>{{ __('common.no') }}</option>
                    </select>
                </x-wizard-field>
                <x-wizard-field :label="__('loans.is_widowed')" for="is_widowed" :required="true">
                    <select name="is_widowed" id="is_widowed" :required="step === 3" class="app-select">
                        <option value="">{{ __('loans.select_yes_no') }}</option>
                        <option value="1" @selected((string) $fd('is_widowed') === '1')>{{ __('common.yes') }}</option>
                        <option value="0" @selected((string) $fd('is_widowed') === '0')>{{ __('common.no') }}</option>
                    </select>
                </x-wizard-field>
            </div>
        </div>

        <div data-wizard-step="4" class="app-card app-card-padded wizard-panel" :class="step === 4 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">4. {{ __('loans.wizard_steps.4') }}</h3>
            <div class="wizard-form-grid wizard-form-grid-2">
                <x-wizard-field :label="__('loans.guarantor_name')" for="guarantor_name">
                    <input type="text" name="guarantor_name" id="guarantor_name" value="{{ $fd('guarantor_name') }}" class="app-input">
                </x-wizard-field>
                <x-wizard-field :label="__('loans.guarantor_phone')" for="guarantor_phone">
                    @include('partials.inputs.phone-input', [
                        'name' => 'guarantor_phone',
                        'value' => $fd('guarantor_phone'),
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
                <x-wizard-field :label="__('loans.guarantor_nin')" for="guarantor_nin" class="wizard-form-grid-span-2">
                    @include('partials.inputs.nin-input', [
                        'name' => 'guarantor_nin',
                        'value' => $fd('guarantor_nin'),
                        'class' => 'w-full app-input',
                    ])
                </x-wizard-field>
            </div>

            <div class="wizard-section mt-6">
                <p class="wizard-section-title">{{ __('loans.guarantor_location') }}</p>
                <div class="wizard-form-grid wizard-form-grid-2">
                    <x-wizard-field :label="__('geo.region')" for="guarantor_region_id" :required="true">
                        <select name="guarantor_region_id" id="guarantor_region_id" x-model="guarantorRegion"
                            @change="guarantorDistrict=''; guarantorCouncil=''; guarantorWard=''; guarantorStreet=''; guarantorDistricts=[]; guarantorCouncils=[]; guarantorWards=[]; guarantorStreets=[]; loadGuarantorDistricts(guarantorRegion);"
                            :required="step === 4" class="app-select">
                            <option value="">-- {{ __('geo.select_region') }} --</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" @selected((string) $fd('guarantor_region_id') === (string) $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.district')" for="guarantor_district_id" :required="true">
                        <select name="guarantor_district_id" id="guarantor_district_id" x-model="guarantorDistrict"
                            @change="guarantorCouncil=''; guarantorWard=''; guarantorStreet=''; guarantorCouncils=[]; guarantorWards=[]; guarantorStreets=[]; loadGuarantorCouncils(guarantorDistrict);"
                            :disabled="!guarantorDistricts.length || guarantorLoading" :required="step === 4" class="app-select">
                            <option value="">-- {{ __('geo.select_district') }} --</option>
                            <template x-for="district in guarantorDistricts" :key="district.id">
                                <option :value="String(district.id)" x-text="district.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.council')" for="guarantor_council_id" :required="true">
                        <select name="guarantor_council_id" id="guarantor_council_id" x-model="guarantorCouncil"
                            @change="guarantorWard=''; guarantorStreet=''; guarantorWards=[]; guarantorStreets=[]; loadGuarantorWards(guarantorCouncil);"
                            :disabled="!guarantorCouncils.length || guarantorLoading" :required="step === 4" class="app-select">
                            <option value="">-- {{ __('geo.select_council') }} --</option>
                            <template x-for="council in guarantorCouncils" :key="council.id">
                                <option :value="String(council.id)" x-text="council.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.ward')" for="guarantor_ward_id" :required="true">
                        <select name="guarantor_ward_id" id="guarantor_ward_id" x-model="guarantorWard"
                            @change="guarantorStreet=''; guarantorStreets=[]; loadGuarantorStreets(guarantorWard);"
                            :disabled="!guarantorWards.length || guarantorLoading" :required="step === 4" class="app-select">
                            <option value="">-- {{ __('geo.select_ward') }} --</option>
                            <template x-for="ward in guarantorWards" :key="ward.id">
                                <option :value="String(ward.id)" x-text="ward.name"></option>
                            </template>
                        </select>
                    </x-wizard-field>

                    <x-wizard-field :label="__('geo.street')" for="guarantor_street_id" :required="true" class="wizard-form-grid-span-2">
                        <select name="guarantor_street_id" id="guarantor_street_id" x-model="guarantorStreet"
                            :disabled="!guarantorStreets.length || guarantorLoading" :required="step === 4" class="app-select">
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
                    <x-slot:inputAttributes>@if(!($editing ?? false)) :required="step === 4" @endif</x-slot:inputAttributes>
                </x-document-upload>
            </div>
        </div>

        <div data-wizard-step="5" class="app-card app-card-padded wizard-panel" :class="step === 5 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">5. {{ __('loans.wizard_steps.5') }}</h3>
            <x-wizard-field :label="__('loans.requested_amount')" for="requested_amount" :required="true">
                @include('partials.inputs.amount-input', [
                    'name' => 'requested_amount',
                    'value' => $fd('requested_amount'),
                    'inputAttributes' => ':required="step === 5"',
                ])
            </x-wizard-field>
        </div>

        <div data-wizard-step="6" class="app-card app-card-padded wizard-panel" :class="step === 6 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">6. {{ __('loans.wizard_steps.6') }}</h3>
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

        <div data-wizard-step="7" class="app-card app-card-padded wizard-panel" :class="step === 7 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">7. {{ __('loans.wizard_steps.7') }}</h3>
            <label class="flex items-start gap-3 cursor-pointer text-slate-900 dark:text-white">
                <input type="checkbox" name="declaration" value="1" :required="step === 7" @checked($fd('declaration')) class="mt-1 w-5 h-5 accent-indigo-600">
                <span>{{ __('loans.confirm_accuracy') }}</span>
            </label>
        </div>

        <div class="app-card app-card-padded flex flex-wrap justify-between items-center gap-3">
            <button type="button" x-show="step > 1" x-cloak @click="step--" class="app-btn app-btn-secondary">{{ __('common.back') }}</button>
            <div class="flex flex-wrap gap-3 ml-auto">
                @if(!($editing ?? false))
                <button type="submit" name="form_action" value="save_draft" formnovalidate @click="prepareDraftSubmit()" class="app-btn app-btn-ghost">{{ __('loans.save_draft') }}</button>
                @endif
                <button type="button" x-show="step < totalSteps" x-cloak @click="nextStep()" class="app-btn app-btn-primary">{{ __('common.next') }}</button>
                <button type="submit" x-show="step === totalSteps" x-cloak class="app-btn app-btn-success">{{ ($editing ?? false) ? __('loans.update_application') : __('loans.submit_application') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
