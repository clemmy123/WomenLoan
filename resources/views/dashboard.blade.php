@extends('layouts.app')

@section('title', __('nav.dashboard'))

@section('content')
<div class="page">
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('dashboard.overview') }}</h1>
            <p class="page-subtitle capitalize">
                {{ str_replace('_', ' ', $user->displayRole()) }} · {{ now()->format('l, d M Y') }}
            </p>
        </div>
        @can('view reports')
            <a href="{{ route('reports.index') }}" class="app-btn app-btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                {{ __('nav.reports') }}
            </a>
        @endcan
    </div>

    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if($user->hasRole('applicant'))
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 p-5 text-white ">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
                <p class="text-sm text-indigo-100 font-medium">{{ __('nav.my_loans') }}</p>
                <p class="text-4xl font-bold mt-2">{{ $stats['my_loans'] }}</p>
            </div>
        @else
            <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wide">{{ __('dashboard.total_applications') }}</p>
                    <span class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                        <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-3">{{ $stats['total'] }}</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">+{{ $stats['this_month'] }} {{ __('dashboard.this_month') }}</p>
            </div>
        @endif

        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wide">{{ __('dashboard.pending_review') }}</p>
                <span class="h-8 w-8 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center">
                    <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-3">{{ $stats['pending'] }}</p>
        </div>

        @if(!$user->hasRole('applicant'))
            <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wide">{{ __('dashboard.approved') }}</p>
                    <span class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center">
                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-3">{{ $stats['approved'] }}</p>
            </div>

            <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wide">{{ __('dashboard.total_disbursed') }}</p>
                    <span class="h-8 w-8 rounded-xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center">
                        <svg class="h-4 w-4 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </span>
                </div>
                <p class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white mt-3">{{ format_tzs($stats['total_amount']) }}</p>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ $stats['disbursed'] }} {{ __('dashboard.disbursed') }}</p>
            </div>
        @else
            <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5  sm:col-span-2">
                <p class="text-xs font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wide">{{ __('dashboard.disbursed') }}</p>
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-3">{{ $stats['disbursed'] }}</p>
            </div>
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
    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h2 class="font-bold text-slate-900">{{ __('dashboard.recent_applications') }}</h2>
            <a href="{{ route('loan-applications.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('dashboard.view_all') }} →</a>
        </div>
        @if($recentLoans->count())
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.track_id') }}</th>
                        <th>{{ __('dashboard.applicant') }}</th>
                        <th>{{ __('dashboard.amount') }}</th>
                        <th>{{ __('dashboard.step') }}</th>
                        <th>{{ __('dashboard.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLoans as $loan)
                    <tr>
                        <td>
                            <a href="{{ route('loan-applications.show', $loan) }}" class="app-table-link">{{ $loan->loan_track_id }}</a>
                        </td>
                        <td class="text-slate-700 dark:text-zinc-300">{{ $loan->applicant?->full_name ?? '—' }}</td>
                        <td class="font-medium">{{ format_tzs($loan->requested_amount) }}</td>
                        <td>@include('partials.badge', ['variant' => 'secondary', 'text' => $loan->current_step.'/9'])</td>
                        <td>@include('partials.loan-status-badge', ['status' => $loan->status])</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="app-table-empty">{{ __('dashboard.no_data') }}</p>
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
