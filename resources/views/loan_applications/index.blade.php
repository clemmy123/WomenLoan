@extends('layouts.app')

@section('title', __('loans.my_applications'))

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                @if(auth()->user()?->hasRole('chief'))
                    {{ __('nav.assign_accountant_queue') }}
                @elseif(auth()->user()?->hasRole('accountant'))
                    {{ __('nav.my_disbursements') }}
                @else
                    {{ __('loans.title') }}
                @endif
            </h1>
            <p class="page-subtitle">
                @if(auth()->user()?->hasRole('chief'))
                    {{ __('loans.chief_queue_subtitle') }}
                @elseif(auth()->user()?->hasRole('accountant'))
                    {{ __('loans.accountant_queue_subtitle') }}
                @else
                    {{ __('loans.apply_subtitle') }}
                @endif
            </p>
        </div>
        @can('create loan application')
        <div class="page-actions flex flex-wrap gap-2">
            @if(($preferredLoanType ?? null) === 'group')
                @if($canSetupGroup ?? false)
                    <a href="{{ route('my-group.create') }}" class="app-btn app-btn-primary">{{ __('groups.setup_title') }}</a>
                @elseif($userGroup ?? null)
                    <a href="{{ route('my-group.show') }}" class="app-btn app-btn-secondary">{{ __('groups.my_group') }}</a>
                @endif
            @endif
            @if($canStartNew ?? true)
                <a href="{{ route('loan-applications.create') }}" class="app-btn app-btn-success">
                    {{ ($preferredLoanType ?? null) === 'group' ? __('loans.continue_as_group') : __('loans.continue_as_individual') }}
                </a>
            @endif
        </div>
        @endcan
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h3 class="font-bold text-slate-900 dark:text-white">{{ __('loans.submitted') }}</h3>
        </div>

        @include('partials.loan-list-toolbar', [
            'action' => route('loan-applications.index'),
            'search' => $search ?? '',
            'status' => $status ?? '',
            'statusOptions' => $listStatusOptions ?? [],
            'showClear' => ($search ?? '') !== '' || ($status ?? '') !== '',
            'clearUrl' => route('loan-applications.index'),
        ])

        @if($loans->total())
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.track_id') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('dashboard.amount') }}</th>
                        <th>{{ __('dashboard.status') }}</th>
                        @if(auth()->user()?->hasRole(['cdo_ward', 'cdo_council', 'cdo_region']))
                            <th>{{ __('loans.business_ward') }}</th>
                        @endif
                        <th>{{ __('common.submitted') }}</th>
                        <th class="w-28">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr>
                            <td>
                                @include('partials.track-id-chip', ['trackId' => $loan->loan_track_id])
                            </td>
                            <td>{{ loan_type_label($loan->loan_type) }}</td>
                            <td>{{ format_tzs($loan->requested_amount) }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1">
                                    @include('partials.badge', ['variant' => 'secondary', 'text' => loan_workflow_step_label($loan->current_step)])
                                    @include('partials.loan-status-badge', ['status' => $loan->status])
                                    @include('partials.cdo-loan-scope-badge', ['loan' => $loan])
                                </div>
                            </td>
                            @if(auth()->user()?->hasRole(['cdo_ward', 'cdo_council', 'cdo_region']))
                                <td>{{ $loan->businessDetails?->ward?->name ?? '—' }}</td>
                            @endif
                            <td>{{ $loan->created_at->translatedFormat('d M Y') }}</td>
                            <td>@include('partials.loan-row-actions', ['loan' => $loan])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="app-card-footer">{{ $loans->links() }}</div>
        @else
        <p class="app-table-empty">
            @if(($search ?? '') !== '' || ($status ?? '') !== '')
                {{ __('dashboard.no_search_results') }}
            @else
                {{ __('loans.no_applications') }}
            @endif
        </p>
        @endif
    </div>

    @if(($canStartNew ?? true) && $drafts->count())
    <div class="app-card app-card-padded">
        <h3 class="font-bold text-lg mb-4 text-slate-900 dark:text-white">{{ __('loans.drafts') }}</h3>
        <ul class="space-y-3">
            @foreach($drafts as $draft)
                <li class="flex flex-wrap justify-between items-center gap-3 border border-slate-200 dark:border-white/10 p-3 rounded-xl">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <p class="font-bold text-slate-800 dark:text-white">{{ __('dashboard.track_id') }}: {{ $draft->track_id }}</p>
                            @include('partials.badge', ['variant' => 'warning', 'text' => __('loans.draft_status')])
                            @include('partials.badge', ['variant' => 'secondary', 'text' => __('loans.draft_step', ['step' => $draft->wizardStep(), 'total' => 6])])
                        </div>
                        <p class="text-slate-500 dark:text-zinc-400 text-sm">{{ __('loans.saved_on', ['date' => $draft->updated_at->translatedFormat('d M Y, H:i')]) }}</p>
                        <p class="text-slate-500 dark:text-zinc-400 text-sm">{{ __('loans.draft_not_submitted') }}</p>
                    </div>
                    <a href="{{ route('loan-applications.create', ['resume_track_id' => $draft->track_id, 'wizard_step' => $draft->wizardStep()]) }}"
                       class="app-btn app-btn-primary">
                        {{ __('loans.resume') }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
