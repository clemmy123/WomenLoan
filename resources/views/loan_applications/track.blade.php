@extends('layouts.app')

@section('title', __('nav.track_loan'))

@section('content')
<div class="app-page app-page-medium">
    @include('partials.page-header', [
        'title' => __('nav.track_loan'),
        'subtitle' => $trackId ?? __('loans.track_search_hint'),
    ])

  @if(!$trackId)
    <div class="app-card app-card-padded">
        <form method="GET" action="{{ route('loans.track') }}" class="space-y-4">
            <x-wizard-field :label="__('dashboard.track_id')" for="track_id" :required="true">
                <input
                    type="text"
                    id="track_id"
                    name="track_id"
                    value="{{ old('track_id') }}"
                    class="app-input font-mono"
                    placeholder="{{ __('loans.track_search_placeholder') }}"
                    required
                    autocomplete="off"
                    spellcheck="false"
                >
            </x-wizard-field>
            @error('track_id')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            <button type="submit" class="app-btn app-btn-primary app-btn-block">{{ __('loans.track_search_button') }}</button>
        </form>
    </div>
  @else
    <div class="app-card app-card-padded space-y-4">
        @if($loan)
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
            @if($canViewFullDetails)
                <a href="{{ route('loan-applications.show', $loan) }}" class="app-btn app-btn-primary app-btn-block">{{ __('loans.view_full_details') }}</a>
            @endif
        @elseif($draft)
            <div class="flex justify-between items-center gap-4">
                <span class="text-slate-500 dark:text-zinc-400">{{ __('common.status') }}</span>
                @include('partials.badge', ['variant' => 'warning', 'text' => __('loans.draft_status')])
            </div>
            <div class="flex justify-between items-center gap-4">
                <span class="text-slate-500 dark:text-zinc-400">{{ __('loans.current_step') }}</span>
                @include('partials.badge', ['variant' => 'secondary', 'text' => __('loans.draft_step', ['step' => $draft->wizardStep(), 'total' => 6])])
            </div>
            @if(!empty($draft->form_data['requested_amount']))
                <div class="flex justify-between items-center gap-4">
                    <span class="text-slate-500 dark:text-zinc-400">{{ __('common.requested') }}</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ format_tzs((int) $draft->form_data['requested_amount']) }}</span>
                </div>
            @endif
            <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('loans.draft_status_hint', ['step' => $draft->wizardStep(), 'total' => 6]) }}</p>
            @if($canResumeDraft)
                <a href="{{ route('loan-applications.create', ['resume_track_id' => $draft->track_id, 'wizard_step' => $draft->wizardStep()]) }}" class="app-btn app-btn-primary app-btn-block">{{ __('loans.resume') }}</a>
            @endif
        @endif

        <a href="{{ route('loans.track') }}" class="app-btn app-btn-secondary app-btn-block">{{ __('common.cancel') }}</a>
    </div>
  @endif
</div>
@endsection
