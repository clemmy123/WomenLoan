@extends('layouts.app')

@section('title', __('analytical_reports.overview_title'))

@section('content')
@php
    $f = $filters;
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('analytical_reports.overview_title') }}</h1>
            <p class="page-subtitle">{{ __('analytical_reports.overview_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($filtersApplied)
                @include('partials.report-export-buttons', [
                    'excelRoute' => route('reports.analytical.export.excel', request()->query()),
                    'pdfRoute' => route('reports.analytical.export.pdf', request()->query()),
                    'excelLabel' => __('analytical_reports.export_excel'),
                    'pdfLabel' => __('analytical_reports.export_pdf'),
                ])
            @endif
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('reports.analytical.overview') }}"
        class="app-card app-card-padded space-y-5"
        x-data="{ filtersOpen: false }"
    >
        @include('partials.filters-toggle-button', [
            'title' => __('analytical_reports.filters'),
            'showLabel' => __('analytical_reports.show_filters'),
            'hideLabel' => __('analytical_reports.hide_filters'),
        ])

        <div
            x-show="filtersOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="space-y-5"
        >
            <div class="wizard-form-grid wizard-form-grid-2 lg:grid-cols-4">
                <div class="wizard-field">
                    <label class="app-label" for="fiscal_year">{{ __('analytical_reports.fiscal_year') }}</label>
                    <select name="fiscal_year" id="fiscal_year" class="app-select" onchange="document.getElementById('use_custom_dates').value=''">
                        @foreach($fiscalYearOptions as $fyKey => $fyLabel)
                            <option value="{{ $fyKey }}" @selected(($f['fiscal_year'] ?? '') === $fyKey)>{{ $fyLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="period">{{ __('analytical_reports.period') }}</label>
                    <select name="period" id="period" class="app-select" onchange="document.getElementById('use_custom_dates').value=''">
                        @foreach(\App\Services\AnalyticalReportService::PERIODS as $period)
                            <option value="{{ $period }}" @selected(($f['period'] ?? '') === $period)>{{ __('reports.period_'.$period) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_from">{{ __('analytical_reports.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $f['date_from'] ?? '' }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_to">{{ __('analytical_reports.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $f['date_to'] ?? '' }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'">
                    <input type="hidden" name="use_custom_dates" id="use_custom_dates" value="{{ ($f['use_custom_dates'] ?? null) === '1' ? '1' : '' }}">
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('analytical_reports.apply_filters') }}</button>
                <a href="{{ route('reports.analytical.overview') }}" class="app-btn app-btn-secondary">{{ __('analytical_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

    @if(! $filtersApplied)
        <div class="app-card app-card-padded text-center">
            <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">{{ __('analytical_reports.apply_filters_prompt') }}</p>
        </div>
    @else
        <section class="analytical-hero">
            <div class="analytical-hero-head">
                <div>
                    <h2 class="analytical-hero-title">{{ __('analytical_reports.summary') }}</h2>
                    <p class="analytical-hero-subtitle">
                        {{ __('analytical_reports.fiscal_year') }}:
                        {{ $f['fiscal_year'] === \App\Support\FiscalYear::ALL_KEY ? __('analytical_reports.all_years') : $f['fiscal_year'] }}
                        ·
                        {{ __('analytical_reports.period') }}:
                        {{ __('reports.period_'.$f['period']) }}
                        @if($f['date_from'] || $f['date_to'])
                            · {{ $f['date_from'] ?? '—' }} → {{ $f['date_to'] ?? '—' }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="analytical-stat-grid">
                <div class="analytical-stat-card analytical-stat-card--indigo">
                    <div class="analytical-stat-card-body">
                        <p class="analytical-stat-label">{{ __('analytical_reports.individual_count') }}</p>
                        <p class="analytical-stat-value">{{ number_format($summary['individual_count']) }}</p>
                        <p class="analytical-stat-meta">{{ format_tzs($summary['individual_disbursed']) }}</p>
                    </div>
                </div>
                <div class="analytical-stat-card analytical-stat-card--violet">
                    <div class="analytical-stat-card-body">
                        <p class="analytical-stat-label">{{ __('analytical_reports.group_count') }}</p>
                        <p class="analytical-stat-value">{{ number_format($summary['group_count']) }}</p>
                        <p class="analytical-stat-meta">{{ format_tzs($summary['group_disbursed']) }}</p>
                    </div>
                </div>
                <div class="analytical-stat-card analytical-stat-card--emerald">
                    <div class="analytical-stat-card-body">
                        <p class="analytical-stat-label">{{ __('analytical_reports.total_paid') }}</p>
                        <p class="analytical-stat-value" style="font-size:1.2rem">{{ format_tzs($summary['total_paid']) }}</p>
                        <p class="analytical-stat-meta">
                            {{ __('loans.types.individual') }}: {{ format_tzs($summary['individual_paid']) }}
                        </p>
                    </div>
                </div>
                <div class="analytical-stat-card analytical-stat-card--cyan">
                    <div class="analytical-stat-card-body">
                        <p class="analytical-stat-label">{{ __('analytical_reports.total_outstanding') }}</p>
                        <p class="analytical-stat-value" style="font-size:1.2rem">{{ format_tzs($summary['total_outstanding']) }}</p>
                        <p class="analytical-stat-meta">
                            {{ __('analytical_reports.total_disbursed') }}: {{ format_tzs($summary['total_disbursed']) }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="analytical-chart-card">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('analytical_reports.chart_by_type') }}</h2>
                <p class="analytical-chart-help">{{ __('analytical_reports.chart_by_type_help') }}</p>
                <div class="h-64"><canvas id="analyticalTypeChart"></canvas></div>
            </div>
            <div class="analytical-chart-card">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('analytical_reports.chart_repayment') }}</h2>
                <p class="analytical-chart-help">{{ __('analytical_reports.chart_repayment_help') }}</p>
                <div class="h-64 flex items-center justify-center"><canvas id="analyticalRepaymentChart"></canvas></div>
            </div>
            <div class="analytical-chart-card lg:col-span-2">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('analytical_reports.chart_trend') }}</h2>
                <p class="analytical-chart-help">{{ __('analytical_reports.chart_trend_help') }}</p>
                <div class="h-72"><canvas id="analyticalTrendChart"></canvas></div>
            </div>
            <div class="analytical-chart-card lg:col-span-2">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('analytical_reports.chart_region') }}</h2>
                <p class="analytical-chart-help">{{ __('analytical_reports.chart_region_help') }}</p>
                <div class="h-72"><canvas id="analyticalRegionChart"></canvas></div>
            </div>
        </div>

        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('analytical_reports.individual_repayments') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('analytical_reports.col_name') }}</th>
                            <th>{{ __('analytical_reports.col_bank') }}</th>
                            <th>{{ __('analytical_reports.col_phone') }}</th>
                            <th>{{ __('analytical_reports.col_disbursed') }}</th>
                            <th>{{ __('analytical_reports.col_paid') }}</th>
                            <th>{{ __('analytical_reports.col_paid_on') }}</th>
                            <th>{{ __('analytical_reports.col_outstanding') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($individuals as $row)
                        <tr>
                            <td>
                                <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="font-medium text-indigo-600 hover:underline">{{ $row['name'] }}</a>
                                <div class="text-xs text-slate-500 font-mono">{{ $row['track_id'] }}</div>
                            </td>
                            <td>{{ $row['bank'] }}</td>
                            <td>{{ $row['phone'] }}</td>
                            <td>{{ format_tzs($row['disbursed']) }}</td>
                            <td>{{ format_tzs($row['paid']) }}</td>
                            <td class="text-xs text-slate-500">{{ $row['paid_on'] }}</td>
                            <td class="font-semibold text-amber-700 dark:text-amber-400">{{ format_tzs($row['outstanding']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="app-table-empty">{{ __('analytical_reports.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $individuals->links() }}</div>
        </div>

        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('analytical_reports.group_repayments') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('analytical_reports.col_group_name') }}</th>
                            <th>{{ __('analytical_reports.col_members') }}</th>
                            <th>{{ __('analytical_reports.col_location') }}</th>
                            <th>{{ __('analytical_reports.col_disbursed') }}</th>
                            <th>{{ __('analytical_reports.col_paid') }}</th>
                            <th>{{ __('analytical_reports.col_outstanding') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $row)
                        <tr>
                            <td>
                                <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="font-medium text-indigo-600 hover:underline">{{ $row['name'] }}</a>
                                <div class="text-xs text-slate-500 font-mono">{{ $row['track_id'] }}</div>
                            </td>
                            <td>
                                <span class="font-semibold">{{ $row['members_count'] }}</span>
                                @if(!empty($row['members']))
                                    <div class="text-xs text-slate-500 mt-1 max-w-xs">{{ implode(', ', array_slice($row['members'], 0, 4)) }}@if(count($row['members']) > 4)…@endif</div>
                                @endif
                            </td>
                            <td class="text-sm">{{ $row['location'] }}</td>
                            <td>{{ format_tzs($row['disbursed']) }}</td>
                            <td>{{ format_tzs($row['paid']) }}</td>
                            <td class="font-semibold text-amber-700 dark:text-amber-400">{{ format_tzs($row['outstanding']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="app-table-empty">{{ __('analytical_reports.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $groups->links() }}</div>
        </div>
    @endif
</div>
@endsection

@if($filtersApplied)
@push('scripts')
<script type="application/json" id="analytical-chart-data">@json($charts)</script>
@vite(['resources/js/pages/analytical-reports.js'])
@endpush
@endif
