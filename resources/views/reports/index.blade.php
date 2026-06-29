@extends('layouts.app')

@section('title', __('nav.reports'))

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('nav.reports') }}</h1>
            <p class="page-subtitle">{{ __('dashboard.overview') }} · {{ now()->format('F Y') }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
    </div>

    {{-- KPI row --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <div class="flex items-center gap-3">
                <span class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
                <div>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 uppercase font-semibold">{{ __('dashboard.total_applications') }}</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <div class="flex items-center gap-3">
                <span class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 uppercase font-semibold">{{ __('dashboard.pending_review') }}</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <div class="flex items-center gap-3">
                <span class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
                <div>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 uppercase font-semibold">{{ __('dashboard.approved') }}</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['approved'] }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <div class="flex items-center gap-3">
                <span class="h-10 w-10 rounded-xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
                <div>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 uppercase font-semibold">{{ __('dashboard.disbursed') }}</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['disbursed'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 p-6 text-white ">
        <p class="text-sm text-indigo-100 font-medium">{{ __('dashboard.total_disbursed') }}</p>
        <p class="text-3xl lg:text-4xl font-bold mt-2">{{ format_tzs($stats['total_amount']) }}</p>
    </div>

    {{-- Charts grid --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('dashboard.applications_trend') }}</h2>
            <div class="h-64"><canvas id="appsChart"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('dashboard.monthly_disbursements') }}</h2>
            <div class="h-64"><canvas id="disbChart"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('dashboard.status_breakdown') }}</h2>
            <div class="h-64 flex items-center justify-center"><canvas id="statusChart" class="max-h-64"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('dashboard.by_region') }}</h2>
            <div class="h-64"><canvas id="regionChart"></canvas></div>
        </div>
    </div>

    {{-- Pipeline full width --}}
    <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
        <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('dashboard.pipeline_status') }}</h2>
        <div class="h-72"><canvas id="pipelineChart"></canvas></div>
    </div>

    {{-- Full loan table --}}
    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h2 class="font-bold text-slate-900">{{ __('dashboard.all_loans') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.track_id') }}</th>
                        <th>{{ __('dashboard.applicant') }}</th>
                        <th>{{ __('common.region') }}</th>
                        <th>{{ __('dashboard.amount') }}</th>
                        <th>{{ __('dashboard.step') }}</th>
                        <th>{{ __('dashboard.status') }}</th>
                        <th>{{ __('dashboard.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                    <tr>
                        <td>
                            <a href="{{ route('loan-applications.show', $loan) }}" class="font-mono text-xs font-semibold text-indigo-600 hover:underline">{{ $loan->loan_track_id }}</a>
                        </td>
                        <td class="text-slate-700">{{ $loan->applicant?->full_name ?? '—' }}</td>
                        <td class="text-slate-500">{{ $loan->businessDetails?->region?->name ?? '—' }}</td>
                        <td class="font-medium">{{ format_tzs($loan->requested_amount) }}</td>
                        <td>@include('partials.badge', ['variant' => 'secondary', 'text' => $loan->current_step.'/9'])</td>
                        <td>@include('partials.loan-status-badge', ['status' => $loan->status])</td>
                        <td class="text-slate-500 text-xs">{{ $loan->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="app-table-empty">{{ __('dashboard.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="app-card-footer">{{ $loans->links() }}</div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $reportsChartData = [
        'monthly' => $monthly,
        'disbursements' => $disbursements,
        'status' => $statusChart,
        'region' => $regionChart,
        'pipeline' => $pipeline,
    ];
@endphp
<script type="application/json" id="reports-chart-data">@json($reportsChartData)</script>
@vite(['resources/js/pages/reports.js'])
@endpush
