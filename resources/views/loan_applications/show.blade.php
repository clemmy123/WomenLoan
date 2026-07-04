@extends('layouts.app')

@section('title', __('loans.show_title', ['track' => $loan->loan_track_id]))

@section('content')
@php
    $hasWorkflow = loan_has_workflow_actions($loan);
    $applicant = $loan->applicant;
    $business = $loan->businessDetails;
    $applicantStreet = $applicant?->location;
    $applicantWard = $applicantStreet?->ward;
    $applicantCouncil = $applicantWard?->council;
    $applicantDistrict = $applicantCouncil?->district;
    $applicantRegion = $applicantDistrict?->region;
    $yesNo = fn (?bool $value) => $value === null ? null : ($value ? __('common.yes') : __('common.no'));
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <a href="{{ route('loan-applications.index') }}" class="text-sm font-semibold text-slate-500 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-2 inline-block">← {{ __('common.back_to_list') }}</a>
            <h1 class="page-title">{{ $loan->loan_track_id }}</h1>
            <p class="page-subtitle">{{ loan_type_label($loan->loan_type) }} — {{ __('common.step_n_of', ['step' => $loan->current_step, 'total' => 9]) }}</p>
        </div>
        @include('partials.loan-status-badge', ['status' => $loan->status])
        @if($loan->isEditableByApplicant())
        <div class="page-actions">
            <a href="{{ route('loan-applications.edit', $loan) }}" class="app-btn app-btn-primary">{{ __('loans.edit_application') }}</a>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 {{ $hasWorkflow ? 'lg:grid-cols-3' : '' }} gap-6">
        <div class="{{ $hasWorkflow ? 'lg:col-span-2' : '' }} space-y-6">
            <div class="app-card app-card-padded">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('loans.summary') }}</h3>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('dashboard.track_id'), 'value' => $loan->loan_track_id, 'mono' => true])
                    @include('partials.detail-field', ['label' => __('common.type'), 'value' => loan_type_label($loan->loan_type)])
                    @include('partials.detail-field', ['label' => __('dashboard.status'), 'value' => loan_status_label($loan->status)])
                    @include('partials.detail-field', ['label' => __('loans.current_step'), 'value' => __('common.step_n_of', ['step' => $loan->current_step, 'total' => 9])])
                    @include('partials.detail-field', ['label' => __('common.requested'), 'value' => format_tzs($loan->requested_amount)])
                    @include('partials.detail-field', ['label' => __('common.proposed'), 'value' => $loan->proposed_amount ? format_tzs($loan->proposed_amount) : null])
                    @include('partials.detail-field', ['label' => __('dashboard.disbursed'), 'value' => $loan->disbursed_amount ? format_tzs($loan->disbursed_amount) : null])
                    @include('partials.detail-field', ['label' => __('loans.applicant_acceptance'), 'value' => $loan->applicant_acceptance ? ucfirst(str_replace('_', ' ', $loan->applicant_acceptance)) : null])
                    @include('partials.detail-field', ['label' => __('loans.date_issued'), 'value' => $loan->date_issued?->translatedFormat('d M Y')])
                    @include('partials.detail-field', ['label' => __('loans.approved_by'), 'value' => $loan->approved_by])
                    @include('partials.detail-field', ['label' => __('loans.assigned_officer'), 'value' => $loan->officer?->name])
                    @if($loan->loan_type === 'group')
                        @include('partials.detail-field', ['label' => __('loans.loan_group'), 'value' => $loan->group?->name])
                    @endif
                    @include('partials.detail-field', ['label' => __('common.submitted'), 'value' => $loan->created_at->translatedFormat('d M Y, H:i')])
                    @include('partials.detail-field', ['label' => __('loans.last_updated'), 'value' => $loan->updated_at->translatedFormat('d M Y, H:i')])
                    @if(filled($loan->comments))
                        <div class="detail-grid-span-2">
                            @include('partials.detail-field', ['label' => __('loans.comments'), 'value' => $loan->comments])
                        </div>
                    @endif
                </div>
            </div>

            <div class="app-card app-card-padded">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('loans.applicant_information') }}</h3>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('loans.applicant_name'), 'value' => $applicant?->full_name])
                    @include('partials.detail-field', ['label' => __('applicants.first_name'), 'value' => $applicant?->first_name])
                    @include('partials.detail-field', ['label' => __('applicants.middle_name'), 'value' => $applicant?->middle_name])
                    @include('partials.detail-field', ['label' => __('applicants.last_name'), 'value' => $applicant?->last_name])
                    @include('partials.detail-field', ['label' => __('loans.applicant_nin'), 'value' => $applicant?->nin, 'mono' => true])
                    @include('partials.detail-field', ['label' => __('loans.applicant_phone'), 'value' => $applicant?->phone])
                    @include('partials.detail-field', ['label' => __('common.email'), 'value' => $applicant?->email])
                    @include('partials.detail-field', ['label' => __('applicants.dob'), 'value' => $applicant?->dob?->translatedFormat('d M Y')])
                    @include('partials.detail-field', ['label' => __('applicants.sex'), 'value' => $applicant?->sex])
                    @include('partials.detail-field', ['label' => __('applicants.marital_status'), 'value' => $applicant?->marital_status])
                    @include('partials.detail-field', ['label' => __('applicants.nationality'), 'value' => $applicant?->nationality])
                    @include('partials.detail-field', ['label' => __('geo.region'), 'value' => $applicantRegion?->name])
                    @include('partials.detail-field', ['label' => __('geo.district'), 'value' => $applicantDistrict?->name])
                    @include('partials.detail-field', ['label' => __('geo.council'), 'value' => $applicantCouncil?->name])
                    @include('partials.detail-field', ['label' => __('geo.ward'), 'value' => $applicantWard?->name])
                    @include('partials.detail-field', ['label' => __('geo.street'), 'value' => $applicantStreet?->name])
                    @include('partials.detail-field', ['label' => __('loans.has_disability'), 'value' => $yesNo($loan->has_disability)])
                    @include('partials.detail-field', ['label' => __('loans.is_widowed'), 'value' => $yesNo($loan->is_widowed)])
                </div>
            </div>

            @if($business)
            <div class="app-card app-card-padded">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('loans.business_location') }}</h3>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('geo.region'), 'value' => $business->region?->name])
                    @include('partials.detail-field', ['label' => __('geo.district'), 'value' => $business->district?->name])
                    @include('partials.detail-field', ['label' => __('geo.council'), 'value' => $business->council?->name])
                    @include('partials.detail-field', ['label' => __('geo.ward'), 'value' => $business->ward?->name])
                    @include('partials.detail-field', ['label' => __('geo.street'), 'value' => $business->street?->name])
                </div>
            </div>

            <div class="app-card app-card-padded">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('loans.business_details') }}</h3>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('loans.business_name'), 'value' => $business->business_name])
                    @include('partials.detail-field', ['label' => __('loans.business_phone'), 'value' => $business->business_phone])
                    @include('partials.detail-field', ['label' => __('loans.business_email'), 'value' => $business->business_email])
                    @include('partials.detail-field', ['label' => __('loans.business_sector'), 'value' => $business->business_sector])
                    @include('partials.detail-field', ['label' => __('loans.business_type'), 'value' => $business->business_type])
                    @include('partials.detail-field', ['label' => __('loans.tin_number'), 'value' => $business->tin_number, 'mono' => true])
                </div>
            </div>

            <div class="app-card app-card-padded">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('loans.supporting_documents') }}</h3>
                <div class="doc-attachments-grid">
                    @include('partials.detail-field-file', ['label' => __('loans.business_proposal'), 'path' => $business->business_proposal_document])
                    @include('partials.detail-field-file', ['label' => __('loans.business_registration'), 'path' => $business->business_registration_attachment])
                    @include('partials.detail-field-file', ['label' => __('loans.proof_address'), 'path' => $business->proof_address_attachment])
                    @if($loan->loan_type === 'individual')
                        @include('partials.detail-field-file', ['label' => __('loans.application_letter'), 'path' => $business->application_letter])
                        @include('partials.detail-field-file', ['label' => __('loans.bank_statement'), 'path' => $business->bank_statement])
                    @endif
                    @if($loan->loan_type === 'group')
                        @include('partials.detail-field-file', ['label' => __('loans.group_constitution'), 'path' => $business->group_constitution])
                        @include('partials.detail-field-file', ['label' => __('loans.group_muhtasari'), 'path' => $business->group_muhtasari])
                        @include('partials.detail-field-file', ['label' => __('loans.group_certificate'), 'path' => $business->group_certificate])
                        @include('partials.detail-field-file', ['label' => __('loans.bank_statement'), 'path' => $business->bank_statement])
                        @include('partials.detail-field-file', ['label' => __('loans.application_letter'), 'path' => $business->application_letter])
                    @endif
                </div>
            </div>
            @endif

            @if($loan->loan_type === 'group' && $loan->group?->members?->count())
            <div class="app-card app-card-padded">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('groups.group_members') }}</h3>
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
                            @foreach($loan->group->members as $member)
                            <tr>
                                <td>{{ $member->full_name }}@if($member->is_group_leader) ({{ __('groups.group_leader') }})@endif</td>
                                <td class="font-mono text-sm">{{ $member->nin }}</td>
                                <td>{{ $member->age ?? __('common.na') }}</td>
                                <td>{{ $member->sex ?? __('common.na') }}</td>
                                <td>{{ $member->phone }}</td>
                                <td>{{ $member->email ?? __('common.na') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="app-card app-card-padded">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('loans.bank_details') }}</h3>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('loans.bank_name'), 'value' => $loan->bank_name])
                    @include('partials.detail-field', ['label' => __('loans.bank_number'), 'value' => $loan->bank_number, 'mono' => true])
                </div>
            </div>

            <div class="app-card app-card-padded">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('loans.guarantor_information') }}</h3>
                @forelse($loan->guarantors as $guarantor)
                    @if(!$loop->first)
                        <hr class="border-slate-100 dark:border-white/10 my-6">
                    @endif
                    <div class="detail-grid">
                        @include('partials.detail-field', ['label' => __('loans.guarantor_name'), 'value' => $guarantor->name])
                        @include('partials.detail-field', ['label' => __('loans.guarantor_phone'), 'value' => $guarantor->phone])
                        @include('partials.detail-field', ['label' => __('loans.guarantor_nin'), 'value' => $guarantor->id_number, 'mono' => true])
                        @include('partials.detail-field', ['label' => __('loans.guarantor_relationship'), 'value' => $guarantor->relationship])
                        @include('partials.detail-field', ['label' => __('loans.guarantor_occupation'), 'value' => $guarantor->occupation])
                        @include('partials.detail-field', [
                            'label' => __('loans.guarantor_sex'),
                            'value' => match ($guarantor->sex) {
                                'Male' => __('applicants.male'),
                                'Female' => __('applicants.female'),
                                default => null,
                            },
                        ])
                        @if($guarantor->guarantor_letter)
                            @include('partials.detail-field-file', ['label' => __('loans.guarantor_letter'), 'path' => $guarantor->guarantor_letter])
                        @endif
                        @include('partials.detail-field', ['label' => __('geo.region'), 'value' => $guarantor->region?->name])
                        @include('partials.detail-field', ['label' => __('geo.district'), 'value' => $guarantor->district?->name])
                        @include('partials.detail-field', ['label' => __('geo.council'), 'value' => $guarantor->council?->name])
                        @include('partials.detail-field', ['label' => __('geo.ward'), 'value' => $guarantor->ward?->name])
                        @include('partials.detail-field', ['label' => __('geo.street'), 'value' => $guarantor->street?->name])
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('common.na') }}</p>
                @endforelse
            </div>

            @if($loan->approvalLevels->count())
            <div class="app-card app-card-padded">
                <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('loans.approval_history') }}</h3>
                <div class="space-y-3">
                    @foreach($loan->approvalLevels as $level)
                    <div class="flex gap-3 p-3 rounded-xl bg-slate-50 dark:bg-white/5 text-sm">
                        <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs shrink-0">{{ $level->step_number }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ workflow_action_label($level->action_taken) }}</p>
                            <p class="text-slate-500 dark:text-zinc-400 text-xs">{{ $level->user?->name }} — {{ $level->created_at->translatedFormat('d M Y H:i') }}</p>
                            @if($level->proposed_amount)
                                <p class="text-slate-600 dark:text-zinc-300 mt-1">{{ __('common.proposed') }}: {{ format_tzs($level->proposed_amount) }}</p>
                            @endif
                            @if($level->comments)
                                <p class="text-slate-600 dark:text-zinc-300 mt-1">{{ $level->comments }}</p>
                            @endif
                            @if($level->attachment_path)
                                <div class="mt-2">
                                    @include('partials.detail-field-file', [
                                        'label' => workflow_attachment_label($level->action_taken),
                                        'path' => $level->attachment_path,
                                    ])
                                </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        @if($hasWorkflow)
        <div class="space-y-4">
            @include('loan_applications._workflow_actions', ['loan' => $loan, 'accountants' => $accountants])
        </div>
        @endif
    </div>
</div>
@endsection
