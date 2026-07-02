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
                        <input type="text" name="business_phone" id="business_phone" value="{{ $fd('business_phone') }}" :required="step === 2" class="app-input">
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_email')" for="business_email" :required="true">
                        <input type="email" name="business_email" id="business_email" value="{{ $fd('business_email') }}" :required="step === 2" class="app-input">
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_sector')" for="business_sector" :required="true">
                        <input type="text" name="business_sector" id="business_sector" value="{{ $fd('business_sector') }}" :required="step === 2" class="app-input">
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_type')" for="business_type" :required="true">
                        <input type="text" name="business_type" id="business_type" value="{{ $fd('business_type') }}" :required="step === 2" class="app-input">
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.tin_number')" for="tin_number" :required="true" class="wizard-form-grid-span-2">
                        <input type="text" name="tin_number" id="tin_number" value="{{ $fd('tin_number') }}" :required="step === 2" class="app-input">
                    </x-wizard-field>
                </div>
            </div>

            <div class="wizard-section">
                <p class="wizard-section-title">{{ __('loans.supporting_documents') }}</p>
                <div class="wizard-form-grid">
                    <x-wizard-field :label="__('loans.business_proposal')" for="business_proposal_document" :required="!($editing ?? false)">
                        <input type="file" name="business_proposal_document" id="business_proposal_document" accept=".pdf,.doc,.docx" @if(!($editing ?? false)) :required="step === 2" @endif class="app-input">
                        @if(($editing ?? false) && ($editingLoan->businessDetails?->business_proposal_document ?? false))
                            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('loans.keep_existing_file') }}</p>
                        @endif
                    </x-wizard-field>
                    <x-wizard-field :label="__('loans.business_registration')" for="business_registration_attachment">
                        <input type="file" name="business_registration_attachment" id="business_registration_attachment" accept=".pdf,.doc,.docx" class="app-input">
                    </x-wizard-field>
                    <div data-loan-scope="group" x-show="loanType === 'group'" x-cloak class="wizard-form-grid wizard-form-grid-2 wizard-form-grid-span-2">
                        <x-wizard-field :label="__('loans.group_constitution')" for="group_constitution" :required="true">
                            <input type="file" name="group_constitution" id="group_constitution" accept=".pdf,.doc,.docx"
                                @if(!($editing ?? false)) :required="step === 2 && loanType === 'group'" @endif class="app-input">
                            @if(($editing ?? false) && ($editingLoan->businessDetails?->group_constitution ?? false))
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('loans.keep_existing_file') }}</p>
                            @endif
                        </x-wizard-field>
                        <x-wizard-field :label="__('loans.group_muhtasari')" for="group_muhtasari" :required="true">
                            <input type="file" name="group_muhtasari" id="group_muhtasari" accept=".pdf,.doc,.docx"
                                @if(!($editing ?? false)) :required="step === 2 && loanType === 'group'" @endif class="app-input">
                            @if(($editing ?? false) && ($editingLoan->businessDetails?->group_muhtasari ?? false))
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('loans.keep_existing_file') }}</p>
                            @endif
                        </x-wizard-field>
                        <x-wizard-field :label="__('loans.group_certificate')" for="group_certificate" :required="true">
                            <input type="file" name="group_certificate" id="group_certificate" accept=".pdf,.doc,.docx"
                                @if(!($editing ?? false)) :required="step === 2 && loanType === 'group'" @endif class="app-input">
                            @if(($editing ?? false) && ($editingLoan->businessDetails?->group_certificate ?? false))
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('loans.keep_existing_file') }}</p>
                            @endif
                        </x-wizard-field>
                    </div>
                    <div data-loan-scope="shared" class="wizard-form-grid wizard-form-grid-2 wizard-form-grid-span-2">
                        <x-wizard-field :label="__('loans.application_letter')" for="application_letter" :required="true">
                            <input type="file" name="application_letter" id="application_letter" accept=".pdf,.doc,.docx"
                                @if(!($editing ?? false)) :required="step === 2" @endif class="app-input">
                            @if(($editing ?? false) && ($editingLoan->businessDetails?->application_letter ?? false))
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('loans.keep_existing_file') }}</p>
                            @endif
                        </x-wizard-field>
                        <x-wizard-field :label="__('loans.bank_statement')" for="bank_statement" :required="true">
                            <input type="file" name="bank_statement" id="bank_statement" accept=".pdf,.doc,.docx"
                                @if(!($editing ?? false)) :required="step === 2" @endif class="app-input">
                            @if(($editing ?? false) && ($editingLoan->businessDetails?->bank_statement ?? false))
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('loans.keep_existing_file') }}</p>
                            @endif
                        </x-wizard-field>
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
                    <input type="text" name="guarantor_phone" id="guarantor_phone" value="{{ $fd('guarantor_phone') }}" class="app-input">
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
                <x-wizard-field :label="__('loans.guarantor_nin')" for="guarantor_nin" class="wizard-form-grid-span-2">
                    <input type="text" name="guarantor_nin" id="guarantor_nin" value="{{ $fd('guarantor_nin') }}" class="app-input">
                </x-wizard-field>
            </div>
        </div>

        <div data-wizard-step="5" class="app-card app-card-padded wizard-panel" :class="step === 5 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">5. {{ __('loans.wizard_steps.5') }}</h3>
            <x-wizard-field :label="__('loans.requested_amount')" for="requested_amount" :required="true">
                <input type="number" name="requested_amount" id="requested_amount" min="1" step="1" value="{{ $fd('requested_amount') }}" :required="step === 5" class="app-input">
            </x-wizard-field>
        </div>

        <div data-wizard-step="6" class="app-card app-card-padded wizard-panel" :class="step === 6 ? 'wizard-step-active' : 'wizard-step-inactive'">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">6. {{ __('loans.wizard_steps.6') }}</h3>
            <div class="wizard-form-grid wizard-form-grid-2">
                <x-wizard-field :label="__('loans.bank_name')" for="bank_name">
                    <input type="text" name="bank_name" id="bank_name" value="{{ $fd('bank_name') }}" class="app-input">
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
