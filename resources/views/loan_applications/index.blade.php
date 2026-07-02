@extends('layouts.app')

@section('title', __('loans.my_applications'))

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('loans.title') }}</h1>
            <p class="page-subtitle">{{ __('loans.apply_subtitle') }}</p>
        </div>
        @can('create loan application')
        <div class="page-actions flex flex-wrap gap-2">
            @if($canSetupGroup ?? false)
                <a href="{{ route('my-group.create') }}" class="app-btn app-btn-primary">{{ __('groups.setup_title') }}</a>
            @elseif($userGroup ?? null)
                <a href="{{ route('my-group.show') }}" class="app-btn app-btn-secondary">{{ __('groups.my_group') }}</a>
            @endif
            @if($canStartNew ?? true)
                <a href="{{ route('loan-applications.create') }}" class="app-btn app-btn-success">{{ __('loans.start_new') }}</a>
            @endif
        </div>
        @endcan
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h3 class="font-bold text-slate-900 dark:text-white">{{ __('loans.submitted') }}</h3>
        </div>
        @if($loans->count())
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.track_id') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('dashboard.amount') }}</th>
                        <th>{{ __('dashboard.status') }}</th>
                        <th>{{ __('common.submitted') }}</th>
                        <th class="w-28">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr>
                            <td>
                                <a href="{{ route('loan-applications.show', $loan) }}" class="app-table-link">{{ $loan->loan_track_id }}</a>
                            </td>
                            <td>{{ loan_type_label($loan->loan_type) }}</td>
                            <td>{{ format_tzs($loan->requested_amount) }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1">
                                    @include('partials.badge', ['variant' => 'secondary', 'text' => __('common.step_n_of', ['step' => $loan->current_step, 'total' => 9])])
                                    @include('partials.loan-status-badge', ['status' => $loan->status])
                                </div>
                            </td>
                            <td>{{ $loan->created_at->translatedFormat('d M Y') }}</td>
                            <td>
                                @if($loan->isEditableByApplicant())
                                    <a href="{{ route('loan-applications.edit', $loan) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('common.edit') }}</a>
                                @else
                                    <span class="text-slate-400 dark:text-zinc-500 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="app-card-footer">{{ $loans->links() }}</div>
        @else
        <p class="app-table-empty">{{ __('loans.no_applications') }}</p>
        @endif
    </div>

    @if(($canStartNew ?? true) && $drafts->count())
    <div class="app-card app-card-padded">
        <h3 class="font-bold text-lg mb-4 text-slate-900 dark:text-white">{{ __('loans.drafts') }}</h3>
        <ul class="space-y-3">
            @foreach($drafts as $draft)
                <li class="flex flex-wrap justify-between items-center gap-3 border border-slate-200 dark:border-white/10 p-3 rounded-xl">
                    <div>
                        <p class="font-bold text-slate-800 dark:text-white">{{ __('dashboard.track_id') }}: {{ $draft->track_id }}</p>
                        <p class="text-slate-500 dark:text-zinc-400 text-sm">{{ __('loans.saved_on', ['date' => $draft->updated_at->translatedFormat('d M Y, H:i')]) }}</p>
                    </div>
                    <a href="{{ route('loan-applications.create', ['resume_track_id' => $draft->track_id]) }}"
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
