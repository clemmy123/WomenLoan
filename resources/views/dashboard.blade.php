@extends('layouts.app')

@section('title', __('nav.dashboard'))

@section('content')
@php
    $recentFilter = $recentFilter ?? 'all';
    $fyStartLabel = \Carbon\Carbon::parse($fiscalYearFrom)->translatedFormat('d M Y');
    $statFyMeta = __('dashboard.since_fy_start', ['date' => $fyStartLabel]);
    $statCardUrl = function (string $filter, ?string $type = null) {
        $query = ['recent' => $filter];
        if (in_array($type, ['individual', 'group'], true)) {
            $query['type'] = $type;
        }

        return route('dashboard', $query).'#recent-applications';
    };
    $recentType = $recentType ?? 'all';
    $disbursedCollection = $disbursedCollection ?? null;
    $hideListActions = in_array($recentFilter, ['all', 'approved', 'disbursed'], true);
    $showWorkflowStepOnList = \App\Support\StaffZone::isMinistryLevel(auth()->user());
    $applicationsBreakdown = [
        [
            'label' => __('dashboard.summary_individual'),
            'value' => number_format($stats['individual_count'] ?? 0),
            'url' => $statCardUrl('all', 'individual'),
            'active' => $recentFilter === 'all' && $recentType === 'individual',
            'tone' => 'individual',
        ],
        [
            'label' => __('dashboard.summary_groups'),
            'value' => number_format($stats['group_members_count'] ?? 0),
            'url' => $statCardUrl('all', 'group'),
            'active' => $recentFilter === 'all' && $recentType === 'group',
            'tone' => 'group',
        ],
    ];
    $statIconApplications = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
    $statIconPending = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $statIconApproved = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    $statIconDisbursed = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>';
@endphp
<div class="app-page">
    {{-- Header --}}
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title lg:text-3xl">{{ __('dashboard.overview') }}</h1>
            <p class="app-page-subtitle capitalize">
                {{ str_replace('_', ' ', $user->displayRole()) }} · {{ now()->format('l, d M Y') }}
                · {{ __('dashboard.fiscal_year_scope', ['year' => $fiscalYear]) }}
            </p>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="dashboard-stats-grid">
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
                'breakdown' => $applicationsBreakdown,
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
                'url' => $statCardUrl('ready_for_disbursement'),
                'gradient' => 'cyan',
                'label' => __('dashboard.ready_to_disburse'),
                'value' => $stats['ready_for_disbursement'],
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
                'breakdown' => $applicationsBreakdown,
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
                'meta' => $statFyMeta,
                'ariaLabel' => __('dashboard.total_applications') . ' — ' . __('dashboard.view_in_recent_list'),
                'icon' => $statIconApplications,
                'breakdown' => $applicationsBreakdown,
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
                'value' => $stats['approved_total'],
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
    <div class="dashboard-charts-grid">
        <div class="dashboard-chart-card">
            <div class="dashboard-chart-card-head">
                <h2 class="dashboard-chart-title">{{ __('dashboard.applications_trend') }}</h2>
                <p class="dashboard-chart-subtitle">{{ __('dashboard.since_fy_start', ['date' => $fyStartLabel]) }}</p>
            </div>
            <div class="dashboard-chart-canvas"><canvas id="trendChart"></canvas></div>
        </div>

        @if(!$user->hasRole('applicant'))
        <div class="dashboard-chart-card">
            <h2 class="dashboard-chart-title dashboard-chart-title--solo">{{ __('dashboard.pipeline_status') }}</h2>
            <div class="dashboard-chart-canvas"><canvas id="pipelineChart"></canvas></div>
        </div>
        @endif
    </div>

    {{-- Recent applications --}}
    <div id="recent-applications" class="dashboard-recent-block scroll-mt-6">
        @if($recentFilter === 'disbursed' && $disbursedCollection)
            @php
                $disbursedAll = $disbursedCollection['all'] ?? $disbursedCollection;
                $disbursedIndividual = $disbursedCollection['individual'] ?? $disbursedCollection;
                $disbursedGroup = $disbursedCollection['group'] ?? $disbursedCollection;
            @endphp
            @include('partials.repayment-summary-strip', [
                'title' => __('repayments.summary_title'),
                'copy' => __('dashboard.disbursed_summary_copy', [
                    'all' => number_format($disbursedAll['count'] ?? 0),
                    'individual' => number_format($disbursedIndividual['count'] ?? 0),
                    'group' => number_format($disbursedGroup['count'] ?? 0),
                ]),
                'rate' => $disbursedCollection['collection_rate'],
                'breakdown' => [
                    [
                        'label' => __('dashboard.summary_all'),
                        'value' => format_tzs($disbursedAll['total_disbursed'] ?? 0),
                        'meta' => trans_choice('dashboard.disbursed_summary_loans', (int) ($disbursedAll['count'] ?? 0), ['count' => number_format($disbursedAll['count'] ?? 0)]),
                        'url' => $statCardUrl('disbursed'),
                        'active' => $recentType === 'all',
                        'tone' => 'all',
                    ],
                    [
                        'label' => __('dashboard.summary_individual'),
                        'value' => format_tzs($disbursedIndividual['total_disbursed'] ?? 0),
                        'meta' => trans_choice('dashboard.disbursed_summary_loans', (int) ($disbursedIndividual['count'] ?? 0), ['count' => number_format($disbursedIndividual['count'] ?? 0)]),
                        'url' => $statCardUrl('disbursed', 'individual'),
                        'active' => $recentType === 'individual',
                        'tone' => 'individual',
                    ],
                    [
                        'label' => __('dashboard.summary_groups'),
                        'value' => format_tzs($disbursedGroup['total_disbursed'] ?? 0),
                        'meta' => trans_choice('dashboard.disbursed_summary_loans', (int) ($disbursedGroup['count'] ?? 0), ['count' => number_format($disbursedGroup['count'] ?? 0)]),
                        'url' => $statCardUrl('disbursed', 'group'),
                        'active' => $recentType === 'group',
                        'tone' => 'group',
                    ],
                ],
                'metrics' => [
                    [
                        'label' => __('repayments.disbursed_col'),
                        'value' => format_tzs($disbursedCollection['total_disbursed']),
                    ],
                    [
                        'label' => __('repayments.amount_paid_col'),
                        'value' => format_tzs($disbursedCollection['total_paid']),
                        'tone' => 'paid',
                    ],
                    [
                        'label' => __('repayments.outstanding'),
                        'value' => format_tzs($disbursedCollection['total_outstanding']),
                        'tone' => 'outstanding',
                    ],
                ],
            ])
        @endif

        <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <div>
                @if($user->hasRole('chief'))
                    <h2 class="app-card-title">{{ __('dashboard.chief_queue_title') }}</h2>
                    <p>{{ __('dashboard.chief_queue_help') }}</p>
                @elseif($user->hasRole('accountant'))
                    <h2 class="app-card-title">{{ __('dashboard.accountant_queue_title') }}</h2>
                    <p>{{ __('dashboard.accountant_queue_help') }}</p>
                @else
                    <h2 class="app-card-title">{{ __('dashboard.recent_applications') }}</h2>
                    @if($recentFilter !== 'all')
                        <p>{{ __('dashboard.recent_filter_' . $recentFilter) }}</p>
                    @elseif($recentType !== 'all')
                        <p>{{ __('dashboard.recent_filter_' . $recentType) }}</p>
                    @endif
                @endif
            </div>
            <a href="{{ route('loan-applications.index') }}" class="app-card-link">{{ __('dashboard.view_all') }} →</a>
        </div>

        @include('partials.loan-list-toolbar', [
            'action' => route('dashboard') . '#recent-applications',
            'search' => $recentSearch,
            'sort' => $recentSort,
            'sortOptions' => $recentSortOptions,
            'hiddenFields' => array_filter([
                'recent' => $recentFilter,
                'type' => $recentType !== 'all' ? $recentType : null,
            ]),
            'showClear' => $recentSearch !== '' || $recentSort !== 'newest' || $recentType !== 'all',
            'clearUrl' => route('dashboard', array_filter([
                'recent' => $recentFilter,
            ])) . '#recent-applications',
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
                        @if($showWorkflowStepOnList)
                            <th>{{ __('dashboard.step') }}</th>
                        @endif
                        <th>{{ __('dashboard.status') }}</th>
                        @unless($hideListActions)
                            <th class="w-24">{{ __('common.actions') }}</th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLoans as $loan)
                    @php $needsAction = ! $hideListActions && loan_needs_user_action($loan); @endphp
                    <tr @class(['loan-row--needs-action' => $needsAction])>
                        <td>
                            @include('partials.track-id-chip', ['trackId' => $loan->loan_track_id])
                        </td>
                        <td>{{ loan_type_label($loan->loan_type) }}</td>
                        <td class="text-slate-700 dark:text-zinc-300">{{ loan_display_name($loan) }}</td>
                        <td>{{ $loan->businessDetails?->ward?->name ?? '—' }}</td>
                        <td class="font-medium">{{ format_tzs($recentFilter === 'disbursed' ? ($loan->disbursed_amount ?: $loan->requested_amount) : $loan->requested_amount) }}</td>
                        @if($showWorkflowStepOnList)
                            <td>@include('partials.badge', ['variant' => 'secondary', 'text' => loan_workflow_step_label($loan->current_step)])</td>
                        @endif
                        <td>
                            <div class="flex flex-wrap items-center gap-1">
                                @unless($hideListActions)
                                    @include('partials.loan-action-needed-badge', ['loan' => $loan])
                                @endunless
                                @include('partials.loan-status-badge', ['status' => $loan->status])
                                @include('partials.cdo-loan-scope-badge', ['loan' => $loan])
                            </div>
                        </td>
                        @unless($hideListActions)
                            <td>@include('partials.loan-row-actions', ['loan' => $loan])</td>
                        @endunless
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
@include('partials.chart-js', ['module' => 'js/pages/dashboard.js'])
@endpush
