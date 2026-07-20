@php
    $user = auth()->user();
    $auth = app(\App\Services\WorkflowAuthorizationService::class);
    $step = $loan->current_step;

    $canForwardMinistry = $auth->canPerform($user, $loan, 'forward_ministry');
    $canProposeAmount = $auth->canPerform($user, $loan, 'propose_amount');
    $canApplicantRespond = $auth->canPerform($user, $loan, 'accept_amount');
    $canForwardAssDir = $auth->canPerform($user, $loan, 'forward_ass_dir');
    $canForwardDirector = $auth->canPerform($user, $loan, 'forward_director');
    $canForwardKm = $auth->canPerform($user, $loan, 'forward_km');
    $canApproveKm = $auth->canPerform($user, $loan, 'approve_km');
    $canAssignAccountant = $auth->canPerform($user, $loan, 'assign_accountant');
    $canDisburse = $auth->canPerform($user, $loan, 'disburse');
    $canRollback = $auth->canPerform($user, $loan, 'rollback_step');
    $rollbackToApplicant = $canRollback && $step === 1 && $loan->status === 'received';
    $rollbackLabel = $rollbackToApplicant
        ? __('workflow.buttons.rollback_to_applicant')
        : __('workflow.buttons.rollback_step');

    $primaryModal = null;
    $actionTitle = null;
    $submitLabel = __('workflow.buttons.submit');
    $submitClass = 'app-btn app-btn-primary app-btn-block';

    if ($canForwardMinistry) {
        $primaryModal = 'forward_ministry';
        $actionTitle = __('workflow.action_titles.forward_ministry');
    } elseif ($canProposeAmount) {
        $primaryModal = 'propose_amount';
        $actionTitle = __('workflow.action_titles.propose_amount');
    } elseif ($canApplicantRespond) {
        $actionTitle = __('workflow.action_titles.respond_amount');
    } elseif ($canForwardAssDir) {
        $primaryModal = 'forward_ass_dir';
        $actionTitle = __('workflow.action_titles.forward_ass_dir');
    } elseif ($canForwardDirector) {
        $primaryModal = 'forward_director';
        $actionTitle = __('workflow.action_titles.forward_director');
    } elseif ($canForwardKm) {
        $primaryModal = 'forward_km';
        $actionTitle = __('workflow.action_titles.forward_km');
    } elseif ($canApproveKm) {
        $primaryModal = 'approve_km';
        $actionTitle = __('workflow.action_titles.approve_km');
    } elseif ($canAssignAccountant) {
        $primaryModal = 'assign_accountant';
        $actionTitle = __('workflow.action_titles.assign_accountant');
    } elseif ($canDisburse) {
        $primaryModal = 'disburse';
        $actionTitle = __('workflow.action_titles.disburse');
        $submitClass = 'app-btn app-btn-success app-btn-block';
    } elseif ($canRollback) {
        $actionTitle = __('workflow.action_titles.rollback_only');
    }
    $hasPrimaryAction = $canApplicantRespond || $primaryModal !== null;
@endphp

@if(loan_has_workflow_actions($loan, $user))
<div class="app-card app-card-padded" x-data="{ modal: null }">
    <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ $actionTitle }}</h3>

    <div class="space-y-2">
        @if($canApplicantRespond)
            <p class="text-sm text-slate-600 dark:text-zinc-400 mb-2">{!! __('workflow.proposed_amount', ['amount' => '<strong>'.e(format_tzs($loan->proposed_amount)).'</strong>']) !!}</p>
            <button type="button" @click="modal = 'accept_amount'" class="app-btn app-btn-success app-btn-block">{{ __('workflow.buttons.submit') }}</button>
            <button type="button" @click="modal = 'decline_amount'" class="app-btn app-btn-danger app-btn-block">{{ __('workflow.buttons.decline_amount') }}</button>
        @elseif($primaryModal)
            <button type="button" @click="modal = '{{ $primaryModal }}'" class="{{ $submitClass }}">{{ $submitLabel }}</button>
        @endif

        @if($hasPrimaryAction && $canRollback)
            <div class="workflow-action-or" aria-hidden="true">
                <span>{{ __('workflow.or') }}</span>
            </div>
        @endif

        @if($canRollback)
            <button type="button" @click="modal = 'rollback_step'" class="app-btn app-btn-danger app-btn-block">{{ $rollbackLabel }}</button>
        @endif
    </div>

    @if($canForwardMinistry)
        @include('partials.modal', [
            'name' => 'forward_ministry',
            'title' => __('workflow.action_titles.forward_ministry'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.forward_ministry', compact('loan'))->render(),
        ])
    @endif

    @if($canProposeAmount)
        @include('partials.modal', [
            'name' => 'propose_amount',
            'title' => __('workflow.action_titles.propose_amount'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.propose_amount', compact('loan'))->render(),
        ])
    @endif

    @if($canApplicantRespond)
        @include('partials.modal', [
            'name' => 'accept_amount',
            'title' => __('workflow.action_titles.respond_amount'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.accept_amount', compact('loan'))->render(),
        ])
        @include('partials.modal', [
            'name' => 'decline_amount',
            'title' => __('workflow.buttons.decline_amount'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.decline_amount', compact('loan'))->render(),
        ])
    @endif

    @if($canForwardAssDir)
        @include('partials.modal', [
            'name' => 'forward_ass_dir',
            'title' => __('workflow.action_titles.forward_ass_dir'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.forward_ass_dir', compact('loan'))->render(),
        ])
    @endif

    @if($canForwardDirector)
        @include('partials.modal', [
            'name' => 'forward_director',
            'title' => __('workflow.action_titles.forward_director'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.forward_director', compact('loan'))->render(),
        ])
    @endif

    @if($canForwardKm)
        @include('partials.modal', [
            'name' => 'forward_km',
            'title' => __('workflow.action_titles.forward_km'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.forward_km', compact('loan'))->render(),
        ])
    @endif

    @if($canApproveKm)
        @include('partials.modal', [
            'name' => 'approve_km',
            'title' => __('workflow.action_titles.approve_km'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.approve_km', compact('loan'))->render(),
        ])
    @endif

    @if($canAssignAccountant)
        @include('partials.modal', [
            'name' => 'assign_accountant',
            'title' => __('workflow.action_titles.assign_accountant'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.assign_accountant', compact('loan', 'accountants'))->render(),
        ])
    @endif

    @if($canDisburse)
        @include('partials.modal', [
            'name' => 'disburse',
            'title' => __('workflow.action_titles.disburse'),
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.disburse', compact('loan'))->render(),
        ])
    @endif

    @if($canRollback)
        @include('partials.modal', [
            'name' => 'rollback_step',
            'title' => $rollbackLabel,
            'wide' => true,
            'body' => view('loan_applications._workflow_forms.rollback_step', compact('loan', 'rollbackToApplicant', 'rollbackLabel'))->render(),
        ])
    @endif
</div>
@endif
