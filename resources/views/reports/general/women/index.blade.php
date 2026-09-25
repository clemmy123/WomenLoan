@extends('layouts.app')

@section('title', __('general_reports.title'))

@section('content')
@php
    $f = $filters;
    $view = $viewMode ?? 'all';
    $reportFiltersBoot = [
        'selectedPrimary' => (string) ($f['loan_type'] ?? ''),
        'selectedPeriod' => (string) ($f['period'] ?? 'annually'),
        'filtersOpen' => ! $filtersApplied,
        'revealTimeFilters' => (bool) $filtersApplied,
        'primarySelectId' => 'loan_type',
        'hasFiscalYear' => false,
        'hasPeriod' => true,
        'hasDates' => false,
        'hasSort' => false,
        'submitOnPeriodChange' => true,
    ];
@endphp
<div class="app-page">
    <form
        method="GET"
        action="{{ route('reports.general.women.index') }}"
        class="app-page-report"
        data-report-filters='@json($reportFiltersBoot)'
    >
        <input type="hidden" name="applied" value="1">
    <div class="app-page-header">
        <div>
            <div class="app-page-heading-row">
                <h1 class="app-page-title lg:text-3xl">{{ __('general_reports.title') }}</h1>
                @include('partials.period-segmented', [
                    'periods' => \App\Services\GeneralReportService::PERIODS,
                    'compact' => true,
                    'submitOnChange' => true,
                ])
            </div>
            <p class="app-page-subtitle">{{ __('general_reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($filtersApplied)
                @include('partials.report-export-buttons', [
                    'excelRoute' => route('reports.general.women.export.excel', request()->query()),
                    'pdfRoute' => route('reports.general.women.export.pdf', request()->query()),
                    'excelLabel' => __('general_reports.export_excel'),
                    'pdfLabel' => __('general_reports.export_pdf'),
                ])
            @endif
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <div class="app-card app-card-padded space-y-5">

        @include('partials.filters-toggle-button', [
            'title' => __('general_reports.filters'),
            'showLabel' => __('general_reports.show_filters'),
            'hideLabel' => __('general_reports.hide_filters'),
        ])

        <div id="filters-panel" class="collapse space-y-5">
            <div class="wizard-form-grid wizard-form-grid-2 lg:grid-cols-3">
                <div class="wizard-field">
                    <label class="app-label" for="loan_type">{{ __('general_reports.loan_type') }}</label>
                    <div class="app-filter-control">
                        <select
                            name="loan_type"
                            id="loan_type"
                            class="app-select"
                            data-primary-filter
                        >
                            <option value="">{{ __('general_reports.all_types') }}</option>
                            <option value="individual">{{ __('loans.types.individual') }}</option>
                            <option value="group">{{ __('loans.types.group') }}</option>
                        </select>
                        <button
                            type="button"
                            class="app-filter-clear-inside"
                            data-filter-clear="primary"
                            title="{{ __('common.clear') }}"
                            aria-label="{{ __('common.clear') }}"
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('general_reports.apply_filters') }}</button>
                <a href="{{ route('reports.general.women.index') }}" class="app-btn app-btn-secondary">{{ __('general_reports.reset_filters') }}</a>
            </div>
        </div>
    </div>
    </form>

    @if(! $filtersApplied)
        <div class="app-card app-card-padded text-center">
            <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">{{ __('general_reports.apply_filters_prompt') }}</p>
        </div>
    @else
        <div class="app-card app-card-padded space-y-4">
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('general_reports.summary') }}</h2>
                <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ __('general_reports.women_count') }}</p>
                <p class="mt-1 text-4xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['women_count']) }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ __('general_reports.bucket_applied') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ number_format($summary['applied'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ __('general_reports.bucket_received') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($summary['received'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ __('general_reports.bucket_processing') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($summary['processing'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ __('general_reports.bucket_missed') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-rose-600 dark:text-rose-400">{{ number_format($summary['missed'] ?? 0) }}</dd>
                </div>
            </dl>
            @if($view === 'all' || $view === 'group')
                <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @if($view === 'all')
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ __('general_reports.individual_count') }}</dt>
                            <dd class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ number_format($summary['individual_count']) }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ __('general_reports.group_count') }}</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ number_format($summary['group_count']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ __('general_reports.group_members_count') }}</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ number_format($summary['group_members_count']) }}</dd>
                    </div>
                </dl>
            @endif
        </div>

        @php
            $chartScope = match ($view) {
                'individual' => __('general_reports.chart_scope_individual'),
                'group' => __('general_reports.chart_scope_group'),
                default => __('general_reports.chart_scope_all'),
            };
        @endphp
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
                <h2 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('general_reports.chart_pie_title') }}</h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mb-1">{{ $chartScope }}</p>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4">{{ __('general_reports.chart_pie_help') }}</p>
                <div class="h-64 flex items-center justify-center"><canvas id="generalReportPieChart"></canvas></div>
            </div>
            <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
                <h2 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('general_reports.chart_bar_title') }}</h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mb-1">{{ $chartScope }}</p>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4">{{ __('general_reports.chart_bar_help') }}</p>
                <div class="h-64"><canvas id="generalReportBarChart"></canvas></div>
            </div>
        </div>
        <p class="text-sm text-slate-600 dark:text-zinc-300">{{ __('general_reports.chart_caption', [
            'applied' => number_format($summary['applied'] ?? 0),
            'received' => number_format($summary['received'] ?? 0),
            'processing' => number_format($summary['processing'] ?? 0),
            'missed' => number_format($summary['missed'] ?? 0),
        ]) }}</p>

        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('general_reports.detail_table') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            @if($view === 'group')
                                <th>{{ __('general_reports.col_group') }}</th>
                                <th>{{ __('general_reports.col_members') }}</th>
                            @else
                                <th>{{ __('general_reports.col_name') }}</th>
                                @if($view === 'all')
                                    <th>{{ __('general_reports.col_status') }}</th>
                                @endif
                                <th>{{ __('general_reports.col_account') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            @if($view === 'group')
                                <td>{{ $row['group_name'] }}</td>
                                <td>{{ $row['members_label'] }}</td>
                            @elseif($view === 'individual')
                                <td>{{ $row['name'] }}</td>
                                <td class="font-mono">{{ $row['account_number'] }}</td>
                            @else
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['loan_type_label'] }}</td>
                                <td class="font-mono">{{ $row['account_number'] }}</td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $view === 'all' ? 3 : 2 }}" class="app-table-empty">{{ __('general_reports.no_results') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $rows->links() }}</div>
        </div>
        <script type="application/json" id="general-reports-chart-data">@json($charts)</script>
        @include('partials.chart-js', ['module' => 'js/pages/general-reports.js'])
    @endif
</div>
@endsection
