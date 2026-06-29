@extends('layouts.app')

@section('title', __('loans.show_title', ['track' => $loan->loan_track_id]))

@section('content')
@php $hasWorkflow = loan_has_workflow_actions($loan); @endphp
<div class="page">
    <div class="page-header">
        <div>
            <a href="{{ route('loan-applications.index') }}" class="text-sm font-semibold text-slate-500 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-2 inline-block">← {{ __('common.back_to_list') }}</a>
            <h1 class="page-title">{{ $loan->loan_track_id }}</h1>
            <p class="page-subtitle">{{ loan_type_label($loan->loan_type) }} — {{ __('common.step_n_of', ['step' => $loan->current_step, 'total' => 9]) }}</p>
        </div>
        @include('partials.loan-status-badge', ['status' => $loan->status])
    </div>

    <div class="grid grid-cols-1 {{ $hasWorkflow ? 'lg:grid-cols-3' : '' }} gap-6">
        <div class="{{ $hasWorkflow ? 'lg:col-span-2' : '' }} space-y-6">
            <div class="app-card app-card-padded">
                <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('loans.details') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500 dark:text-zinc-400">{{ __('common.requested') }}</dt><dd class="font-semibold text-slate-900 dark:text-white">{{ format_tzs($loan->requested_amount) }}</dd></div>
                    <div><dt class="text-slate-500 dark:text-zinc-400">{{ __('common.proposed') }}</dt><dd class="font-semibold text-slate-900 dark:text-white">{{ $loan->proposed_amount ? format_tzs($loan->proposed_amount) : '—' }}</dd></div>
                    <div><dt class="text-slate-500 dark:text-zinc-400">{{ __('dashboard.disbursed') }}</dt><dd class="font-semibold text-slate-900 dark:text-white">{{ $loan->disbursed_amount ? format_tzs($loan->disbursed_amount) : '—' }}</dd></div>
                    <div><dt class="text-slate-500 dark:text-zinc-400">{{ __('dashboard.applicant') }}</dt><dd class="font-semibold text-slate-900 dark:text-white">{{ $loan->applicant?->full_name ?? __('common.na') }}</dd></div>
                    <div><dt class="text-slate-500 dark:text-zinc-400">{{ __('common.business') }}</dt><dd class="font-semibold text-slate-900 dark:text-white">{{ $loan->businessDetails?->business_name ?? __('common.na') }}</dd></div>
                    <div><dt class="text-slate-500 dark:text-zinc-400">{{ __('common.submitted') }}</dt><dd class="font-semibold text-slate-900 dark:text-white">{{ $loan->created_at->translatedFormat('d M Y, H:i') }}</dd></div>
                </dl>
            </div>

            @if($loan->approvalLevels->count())
            <div class="app-card app-card-padded">
                <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('loans.approval_history') }}</h3>
                <div class="space-y-3">
                    @foreach($loan->approvalLevels as $level)
                    <div class="flex gap-3 p-3 rounded-xl bg-slate-50 dark:bg-white/5 text-sm">
                        <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs shrink-0">{{ $level->step_number }}</div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ workflow_action_label($level->action_taken) }}</p>
                            <p class="text-slate-500 dark:text-zinc-400 text-xs">{{ $level->user?->name }} — {{ $level->created_at->translatedFormat('d M Y H:i') }}</p>
                            @if($level->comments)<p class="text-slate-600 dark:text-zinc-300 mt-1">{{ $level->comments }}</p>@endif
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
