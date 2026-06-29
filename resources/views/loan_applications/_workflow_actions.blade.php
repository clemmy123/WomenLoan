@php
    $user = auth()->user();
    $step = $loan->current_step;

    $canReceive = $user->can('receive application') && $step === 1 && $loan->status === 'pending';
    $canForwardMinistry = $user->can('forward to ministry') && $step === 1 && $loan->status === 'received';
    $canProposeAmount = $user->can('propose loan amount') && in_array($step, [2, 4], true);
    $canApplicantRespond = $user->hasRole('applicant') && $step === 3;
    $canForwardAssDir = $user->can('forward to assistant director') && $step === 4;
    $canForwardDirector = $user->can('forward to director') && $step === 5;
    $canForwardKm = $user->can('forward to km') && $step === 6;
    $canApproveKm = $user->can('approve as km') && $step === 7;
    $canAssignAccountant = $user->can('assign accountant') && $step === 8;
    $canDisburse = $user->can('disburse loan') && $step === 9 && $loan->officer_id === $user->id;
@endphp

@if(loan_has_workflow_actions($loan, $user))
<div class="app-card app-card-padded" x-data="{ modal: null }">
    <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('workflow.title') }}</h3>

    <div class="space-y-2">
        @if($canReceive)
            <button type="button" @click="modal = 'receive'" class="app-btn app-btn-success app-btn-block">{{ __('workflow.buttons.receive') }}</button>
        @endif
        @if($canForwardMinistry)
            <button type="button" @click="modal = 'forward_ministry'" class="app-btn app-btn-primary app-btn-block">{{ __('workflow.buttons.forward_ministry') }}</button>
        @endif
        @if($canProposeAmount)
            <button type="button" @click="modal = 'propose_amount'" class="app-btn app-btn-primary app-btn-block">{{ __('workflow.buttons.propose_amount') }}</button>
        @endif
        @if($canApplicantRespond)
            <p class="text-sm text-slate-600 dark:text-zinc-400 mb-2">{!! __('workflow.proposed_amount', ['amount' => '<strong>'.e(format_tzs($loan->proposed_amount)).'</strong>']) !!}</p>
            <button type="button" @click="modal = 'accept_amount'" class="app-btn app-btn-success app-btn-block">{{ __('workflow.buttons.accept_amount') }}</button>
            <button type="button" @click="modal = 'decline_amount'" class="app-btn app-btn-danger app-btn-block">{{ __('workflow.buttons.decline_amount') }}</button>
        @endif
        @if($canForwardAssDir)
            <button type="button" @click="modal = 'forward_ass_dir'" class="app-btn app-btn-primary app-btn-block">{{ __('workflow.buttons.forward_ass_dir') }}</button>
        @endif
        @if($canForwardDirector)
            <button type="button" @click="modal = 'forward_director'" class="app-btn app-btn-primary app-btn-block">{{ __('workflow.buttons.forward_director') }}</button>
        @endif
        @if($canForwardKm)
            <button type="button" @click="modal = 'forward_km'" class="app-btn app-btn-primary app-btn-block">{{ __('workflow.buttons.forward_km') }}</button>
        @endif
        @if($canApproveKm)
            <button type="button" @click="modal = 'approve_km'" class="app-btn app-btn-success app-btn-block">{{ __('workflow.buttons.approve_km') }}</button>
        @endif
        @if($canAssignAccountant)
            <button type="button" @click="modal = 'assign_accountant'" class="app-btn app-btn-primary app-btn-block">{{ __('workflow.buttons.assign_accountant') }}</button>
        @endif
        @if($canDisburse)
            <button type="button" @click="modal = 'disburse'" class="app-btn app-btn-success app-btn-block">{{ __('workflow.buttons.disburse', ['amount' => format_tzs($loan->proposed_amount)]) }}</button>
        @endif
    </div>

    @if($canReceive)
        @include('partials.modal', [
            'name' => 'receive',
            'title' => __('workflow.buttons.receive'),
            'body' => view('loan_applications._workflow_forms.receive', compact('loan'))->render(),
        ])
    @endif

    @if($canForwardMinistry)
        @include('partials.modal', [
            'name' => 'forward_ministry',
            'title' => __('workflow.buttons.forward_ministry'),
            'body' => view('loan_applications._workflow_forms.forward_ministry', compact('loan'))->render(),
        ])
    @endif

    @if($canProposeAmount)
        @include('partials.modal', [
            'name' => 'propose_amount',
            'title' => __('workflow.buttons.propose_amount'),
            'body' => view('loan_applications._workflow_forms.propose_amount', compact('loan'))->render(),
        ])
    @endif

    @if($canApplicantRespond)
        @include('partials.modal', [
            'name' => 'accept_amount',
            'title' => __('workflow.buttons.accept_amount'),
            'body' => view('loan_applications._workflow_forms.accept_amount', compact('loan'))->render(),
        ])
        @include('partials.modal', [
            'name' => 'decline_amount',
            'title' => __('workflow.buttons.decline_amount'),
            'body' => view('loan_applications._workflow_forms.decline_amount', compact('loan'))->render(),
        ])
    @endif

    @if($canForwardAssDir)
        @include('partials.modal', [
            'name' => 'forward_ass_dir',
            'title' => __('workflow.buttons.forward_ass_dir'),
            'body' => view('loan_applications._workflow_forms.forward_ass_dir', compact('loan'))->render(),
        ])
    @endif

    @if($canForwardDirector)
        @include('partials.modal', [
            'name' => 'forward_director',
            'title' => __('workflow.buttons.forward_director'),
            'body' => view('loan_applications._workflow_forms.forward_director', compact('loan'))->render(),
        ])
    @endif

    @if($canForwardKm)
        @include('partials.modal', [
            'name' => 'forward_km',
            'title' => __('workflow.buttons.forward_km'),
            'body' => view('loan_applications._workflow_forms.forward_km', compact('loan'))->render(),
        ])
    @endif

    @if($canApproveKm)
        @include('partials.modal', [
            'name' => 'approve_km',
            'title' => __('workflow.buttons.approve_km'),
            'body' => view('loan_applications._workflow_forms.approve_km', compact('loan'))->render(),
        ])
    @endif

    @if($canAssignAccountant)
        @include('partials.modal', [
            'name' => 'assign_accountant',
            'title' => __('workflow.buttons.assign_accountant'),
            'body' => view('loan_applications._workflow_forms.assign_accountant', compact('loan', 'accountants'))->render(),
        ])
    @endif

    @if($canDisburse)
        @include('partials.modal', [
            'name' => 'disburse',
            'title' => __('workflow.buttons.disburse', ['amount' => format_tzs($loan->proposed_amount)]),
            'body' => view('loan_applications._workflow_forms.disburse', compact('loan'))->render(),
        ])
    @endif
</div>
@endif
