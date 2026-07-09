@extends('layouts.app')

@section('title', __('nav.track_loan'))

@section('content')
<div class="page page-medium">
    @include('partials.page-header', [
        'title' => __('nav.track_loan'),
        'subtitle' => $loan->loan_track_id,
    ])

    <div class="app-card app-card-padded space-y-4">
        <div class="flex justify-between items-center gap-4">
            <span class="text-slate-500 dark:text-zinc-400">{{ __('common.status') }}</span>
            @include('partials.loan-status-badge', ['status' => $loan->status])
        </div>
        <div class="flex justify-between items-center gap-4">
            <span class="text-slate-500 dark:text-zinc-400">{{ __('loans.current_step') }}</span>
            @include('partials.badge', ['variant' => 'secondary', 'text' => loan_workflow_step_label($loan->current_step)])
        </div>
        <div class="flex justify-between items-center gap-4">
            <span class="text-slate-500 dark:text-zinc-400">{{ __('common.requested') }}</span>
            <span class="font-semibold text-slate-900 dark:text-white">{{ format_tzs($loan->requested_amount) }}</span>
        </div>
        <a href="{{ route('loan-applications.show', $loan) }}" class="app-btn app-btn-primary app-btn-block">{{ __('loans.view_full_details') }}</a>
    </div>
</div>
@endsection
