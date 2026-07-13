@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
@php
    $f = $filters;
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ $pageTitle }}</h1>
            <p class="page-subtitle">{{ $pageSubtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($filtersApplied)
                @include('partials.report-export-buttons', [
                    'excelRoute' => route($excelRouteName, request()->query()),
                    'pdfRoute' => route($pdfRouteName, request()->query()),
                    'excelLabel' => __('analytical_reports.export_excel'),
                    'pdfLabel' => __('analytical_reports.export_pdf'),
                ])
            @endif
            <a href="{{ route('reports.analytical.overview') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.analytical_overview') }}</a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route($indexRouteName) }}"
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
                <a href="{{ route($indexRouteName) }}" class="app-btn app-btn-secondary">{{ __('analytical_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

    @if(! $filtersApplied)
        <div class="app-card app-card-padded text-center mt-6">
            <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">{{ __('analytical_reports.apply_filters_prompt') }}</p>
        </div>
    @else
        @include('partials.repayment-summary-strip', [
            'title' => __('analytical_reports.summary'),
            'copy' => $pageSubtitle,
            'rate' => $summary['collection_rate'],
            'rateLabel' => __('analytical_reports.collection_rate_label', ['rate' => $summary['collection_rate']]),
            'metrics' => [
                [
                    'label' => __('analytical_reports.debt_count'),
                    'value' => number_format($summary['count']),
                ],
                [
                    'label' => __('analytical_reports.col_disbursed'),
                    'value' => format_tzs($summary['total_disbursed']),
                ],
                [
                    'label' => __('analytical_reports.col_outstanding'),
                    'value' => format_tzs($summary['total_outstanding']),
                    'tone' => 'outstanding',
                ],
                [
                    'label' => __('analytical_reports.average_elapsed'),
                    'value' => number_format($summary['average_elapsed_days']).' '.__('analytical_reports.days_unit'),
                ],
            ],
        ])

        <div class="app-card overflow-hidden mt-6">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ $listTitle }}</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('analytical_reports.col_name') }}</th>
                            <th>{{ __('analytical_reports.col_disbursed') }}</th>
                            <th>{{ __('analytical_reports.col_outstanding') }}</th>
                            @if($mode === 'overdue')
                                <th>{{ __('analytical_reports.col_elapsed') }}</th>
                            @endif
                            <th>{{ __('dashboard.track_id') }}</th>
                            @if($mode === 'overdue')
                                <th class="w-24">{{ __('common.actions') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr @class(['loan-row--needs-action' => $mode === 'overdue' || ($row['is_overdue'] ?? false)])>
                                <td>
                                    <div class="font-medium text-slate-900 dark:text-white">{{ $row['name'] }}</div>
                                    <div class="text-xs text-slate-500 dark:text-zinc-400">{{ loan_type_label($row['loan_type']) }}</div>
                                </td>
                                <td>{{ format_tzs($row['disbursed']) }}</td>
                                <td class="font-semibold text-amber-700 dark:text-amber-300">{{ format_tzs($row['outstanding']) }}</td>
                                @if($mode === 'overdue')
                                    <td>
                                        <span class="font-medium">{{ $row['elapsed_label'] }}</span>
                                        <div class="text-xs text-slate-500 dark:text-zinc-400">{{ __('analytical_reports.due_since', ['date' => $row['due_date']]) }}</div>
                                    </td>
                                @endif
                                <td>@include('partials.track-id-chip', ['trackId' => $row['track_id']])</td>
                                @if($mode === 'overdue')
                                    <td>
                                        <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="app-btn app-btn-secondary text-xs">{{ __('common.view') }}</a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $mode === 'overdue' ? 6 : 4 }}" class="app-table-empty">{{ __('analytical_reports.no_debt_results') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->total())
                <div class="app-card-footer">{{ $rows->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
