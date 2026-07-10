@extends('layouts.app')

@section('title', __('nav.dashboard'))

@section('content')
@php
    $recentFilter = $recentFilter ?? 'all';
    $statCardUrl = fn (string $filter) => route('dashboard', ['recent' => $filter]) . '#recent-applications';
    $statIconApplications = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
    $statIconPending = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $statIconApproved = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    $statIconDisbursed = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>';
@endphp
<div class="page" x-data="{}">
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('dashboard.overview') }}</h1>
            <p class="page-subtitle capitalize">
                {{ str_replace('_', ' ', $user->displayRole()) }} · {{ now()->format('l, d M Y') }}
            </p>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if($user->hasRole('applicant'))
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('all'),
                'gradient' => 'indigo',
                'label' => __('nav.my_loans'),
                'value' => $stats['my_loans'],
                'ariaLabel' => __('nav.my_loans') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconApplications,
                'constant' => true,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('pending'),
                'gradient' => 'cyan',
                'label' => __('dashboard.pending_review'),
                'value' => $stats['pending'],
                'ariaLabel' => __('dashboard.pending_review') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconPending,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('disbursed'),
                'gradient' => 'emerald',
                'label' => __('dashboard.disbursed'),
                'value' => $stats['disbursed'],
                'ariaLabel' => __('dashboard.disbursed') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconDisbursed,
                'wide' => true,
            ])
        @elseif($user->hasRole('chief'))
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('approved'),
                'gradient' => 'violet',
                'label' => __('dashboard.awaiting_assignment'),
                'value' => $stats['approved'],
                'ariaLabel' => __('dashboard.awaiting_assignment') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconApproved,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('disbursed'),
                'gradient' => 'emerald',
                'label' => __('dashboard.disbursed'),
                'value' => $stats['disbursed'],
                'ariaLabel' => __('dashboard.disbursed') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconDisbursed,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('all'),
                'gradient' => 'indigo',
                'label' => __('dashboard.total_applications'),
                'value' => $stats['total'],
                'ariaLabel' => __('dashboard.total_applications') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconApplications,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('disbursed'),
                'gradient' => 'cyan',
                'label' => __('dashboard.total_disbursed'),
                'value' => format_tzs($stats['total_amount']),
                'valueClass' => 'dashboard-stat-card-value--amount',
                'meta' => $stats['disbursed'] . ' ' . __('dashboard.disbursed'),
                'ariaLabel' => __('dashboard.total_disbursed') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconDisbursed,
            ])
        @elseif($user->hasRole('accountant'))
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('approved'),
                'gradient' => 'cyan',
                'label' => __('dashboard.ready_to_disburse'),
                'value' => $stats['approved'],
                'ariaLabel' => __('dashboard.ready_to_disburse') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconPending,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('disbursed'),
                'gradient' => 'emerald',
                'label' => __('dashboard.disbursed'),
                'value' => $stats['disbursed'],
                'ariaLabel' => __('dashboard.disbursed') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconDisbursed,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('all'),
                'gradient' => 'indigo',
                'label' => __('dashboard.total_applications'),
                'value' => $stats['total'],
                'ariaLabel' => __('dashboard.total_applications') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconApplications,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('disbursed'),
                'gradient' => 'violet',
                'label' => __('dashboard.total_disbursed'),
                'value' => format_tzs($stats['total_amount']),
                'valueClass' => 'dashboard-stat-card-value--amount',
                'meta' => $stats['disbursed'] . ' ' . __('dashboard.disbursed'),
                'ariaLabel' => __('dashboard.total_disbursed') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconApproved,
            ])
        @else
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('all'),
                'gradient' => 'indigo',
                'label' => __('dashboard.total_applications'),
                'value' => $stats['total'],
                'meta' => '+' . $stats['this_month'] . ' ' . __('dashboard.this_month'),
                'metaClass' => 'dashboard-stat-card-meta--positive',
                'ariaLabel' => __('dashboard.total_applications') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconApplications,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('pending'),
                'gradient' => 'cyan',
                'label' => __('dashboard.pending_review'),
                'value' => $stats['pending'],
                'ariaLabel' => __('dashboard.pending_review') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconPending,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('approved'),
                'gradient' => 'violet',
                'label' => __('dashboard.approved'),
                'value' => $stats['approved'],
                'ariaLabel' => __('dashboard.approved') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconApproved,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => $statCardUrl('disbursed'),
                'gradient' => 'emerald',
                'label' => __('dashboard.total_disbursed'),
                'value' => format_tzs($stats['total_amount']),
                'valueClass' => 'dashboard-stat-card-value--amount',
                'meta' => $stats['disbursed'] . ' ' . __('dashboard.disbursed'),
                'ariaLabel' => __('dashboard.total_disbursed') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconDisbursed,
            ])
        @endif
    </div>

    @if($user->hasRole('applicant') && !$user->applicant)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-5 flex items-start gap-4">
            <span class="h-10 w-10 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
            <div>
                <p class="font-semibold text-amber-800 dark:text-amber-300">{{ __('dashboard.complete_profile') }}</p>
                <a href="{{ route('applicants.create') }}" class="inline-block mt-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('nav.register_applicant') }} →</a>
            </div>
        </div>
    @endif

    {{-- Charts row --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">{{ __('dashboard.applications_trend') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">{{ __('dashboard.live') }}</p>
                </div>
            </div>
            <div class="h-56"><canvas id="trendChart"></canvas></div>
        </div>

        @if(!$user->hasRole('applicant'))
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-6">{{ __('dashboard.pipeline_status') }}</h2>
            <div class="h-56"><canvas id="pipelineChart"></canvas></div>
        </div>
        @endif
    </div>

    {{-- Recent applications --}}
    <div id="recent-applications" class="app-card overflow-hidden scroll-mt-6">
        <div class="app-card-header">
            <div>
                @if($user->hasRole('chief'))
                    <h2 class="font-bold text-slate-900 dark:text-white">{{ __('dashboard.chief_queue_title') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">{{ __('dashboard.chief_queue_help') }}</p>
                @elseif($user->hasRole('accountant'))
                    <h2 class="font-bold text-slate-900 dark:text-white">{{ __('dashboard.accountant_queue_title') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">{{ __('dashboard.accountant_queue_help') }}</p>
                @else
                    <h2 class="font-bold text-slate-900 dark:text-white">{{ __('dashboard.recent_applications') }}</h2>
                    @if($recentFilter !== 'all')
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5 font-medium">{{ __('dashboard.recent_filter_' . $recentFilter) }}</p>
                    @endif
                @endif
            </div>
            <a href="{{ route('loan-applications.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('dashboard.view_all') }} →</a>
        </div>

        @include('partials.loan-list-toolbar', [
            'action' => route('dashboard') . '#recent-applications',
            'search' => $recentSearch,
            'sort' => $recentSort,
            'sortOptions' => $recentSortOptions,
            'hiddenFields' => ['recent' => $recentFilter],
            'showClear' => $recentSearch !== '' || $recentSort !== 'newest',
            'clearUrl' => route('dashboard', ['recent' => $recentFilter]) . '#recent-applications',
        ])

        @if($recentLoans->total())
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.track_id') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('loans.list_name') }}</th>
                        <th>{{ __('loans.business_ward') }}</th>
                        <th>{{ __('dashboard.amount') }}</th>
                        <th>{{ __('dashboard.step') }}</th>
                        <th>{{ __('dashboard.status') }}</th>
                        <th class="w-24">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLoans as $loan)
                    @php $needsAction = loan_needs_user_action($loan); @endphp
                    <tr @class(['loan-row--needs-action' => $needsAction])>
                        <td>
                            @include('partials.track-id-chip', ['trackId' => $loan->loan_track_id])
                        </td>
                        <td>{{ loan_type_label($loan->loan_type) }}</td>
                        <td class="text-slate-700 dark:text-zinc-300">{{ loan_display_name($loan) }}</td>
                        <td>{{ $loan->businessDetails?->ward?->name ?? '—' }}</td>
                        <td class="font-medium">{{ format_tzs($loan->requested_amount) }}</td>
                        <td>@include('partials.badge', ['variant' => 'secondary', 'text' => loan_workflow_step_label($loan->current_step)])</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-1">
                                @include('partials.loan-action-needed-badge', ['loan' => $loan])
                                @include('partials.loan-status-badge', ['status' => $loan->status])
                                @include('partials.cdo-loan-scope-badge', ['loan' => $loan])
                            </div>
                        </td>
                        <td>@include('partials.loan-row-actions', ['loan' => $loan])</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="app-card-footer">{{ $recentLoans->links() }}</div>
        @else
        <p class="app-table-empty">
            @if($recentSearch !== '')
                {{ __('dashboard.no_search_results') }}
            @elseif($recentFilter !== 'all')
                {{ __('dashboard.no_results_filter') }}
            @else
                {{ __('dashboard.no_data') }}
            @endif
        </p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@php
    $dashboardChartData = [
        'monthly' => [
            'labels' => $monthly['labels'],
            'data' => $monthly['data'],
            'label' => __('dashboard.total_applications'),
        ],
        'pipeline' => [
            'labels' => $pipeline['shortLabels'],
            'data' => $pipeline['data'],
        ],
        'showPipeline' => ! $user->hasRole('applicant'),
    ];
@endphp
<script type="application/json" id="dashboard-chart-data">@json($dashboardChartData)</script>
@vite(['resources/js/pages/dashboard.js'])
@endpush
