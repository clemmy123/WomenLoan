@extends('layouts.app')

@section('title', __('application_reports.title'))

@section('content')
@php
    $f = $filters;
    $reportFiltersBoot = [
        'selectedPrimary' => (string) ($f['status'] ?? ''),
        'selectedFiscalYear' => (string) ($f['fiscal_year'] ?? ''),
        'defaultFiscalYear' => (string) ($f['fiscal_year'] ?? 'all'),
        'selectedPeriod' => (string) ($f['period'] ?? 'annually'),
        'selectedDateFrom' => (string) (($f['use_custom_dates'] ?? null) === '1' ? ($f['date_from'] ?? '') : ''),
        'selectedDateTo' => (string) (($f['use_custom_dates'] ?? null) === '1' ? ($f['date_to'] ?? '') : ''),
        'useCustomDates' => (($f['use_custom_dates'] ?? null) === '1') ? '1' : '',
        'filtersOpen' => false,
        'revealTimeFilters' => (bool) $filtersApplied,
        'primarySelectId' => 'status',
        'hasSort' => false,
    ];
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('application_reports.title') }}</h1>
            <p class="page-subtitle">{{ __('application_reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($filtersApplied)
                @include('partials.report-export-buttons', [
                    'excelRoute' => route('reports.applications.export.excel', request()->query()),
                    'pdfRoute' => route('reports.applications.export.pdf', request()->query()),
                    'excelLabel' => __('application_reports.export_excel'),
                    'pdfLabel' => __('application_reports.export_pdf'),
                ])
            @endif
            <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.reports_overview') }}</a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('reports.applications.index') }}"
        class="app-card app-card-padded space-y-5"
        x-data="reportFilters(@js($reportFiltersBoot))"
    >
        @include('partials.filters-toggle-button', [
            'title' => __('application_reports.filters'),
            'showLabel' => __('application_reports.show_filters'),
            'hideLabel' => __('application_reports.hide_filters'),
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
            <div class="wizard-form-grid wizard-form-grid-2 lg:grid-cols-3">
                @include('partials.report-time-filters', [
                    'langPrefix' => 'application_reports',
                    'fiscalYearOptions' => $fiscalYearOptions,
                    'periods' => \App\Services\ApplicationReportService::PERIODS,
                    'showSort' => false,
                ])

                <div class="wizard-field">
                    <label class="app-label" for="status">{{ __('application_reports.status') }}</label>
                    <div class="app-filter-control" :class="{ 'has-clear': selectedPrimary }">
                        <select
                            name="status"
                            id="status"
                            class="app-select"
                            x-model="selectedPrimary"
                            @change="onPrimaryChange()"
                        >
                            <option value="">{{ __('application_reports.all_statuses') }}</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ loan_status_label($status) }}</option>
                            @endforeach
                        </select>
                        <button
                            type="button"
                            class="app-filter-clear-inside"
                            x-show="selectedPrimary"
                            x-cloak
                            @click.prevent="clearPrimaryValue()"
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
                <button type="submit" class="app-btn app-btn-primary">{{ __('application_reports.apply_filters') }}</button>
                <a href="{{ route('reports.applications.index') }}" class="app-btn app-btn-secondary">{{ __('application_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

    @if(! $filtersApplied)
        <div class="app-card app-card-padded text-center">
            <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">{{ __('application_reports.apply_filters_prompt') }}</p>
        </div>
    @else
        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('application_reports.detail_table') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('application_reports.track_id') }}</th>
                            <th>{{ __('application_reports.full_name') }}</th>
                            <th>{{ __('application_reports.amount_requested') }}</th>
                            <th>{{ __('application_reports.amount_disbursed') }}</th>
                            <th>{{ __('application_reports.outstanding') }}</th>
                            <th>{{ __('application_reports.amount_repaid') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="font-mono text-xs font-semibold text-indigo-600 hover:underline">{{ $row['track_id'] }}</a>
                            </td>
                            <td class="font-medium text-slate-900 dark:text-white">
                                {{ $row['full_name'] }}
                                @if(($row['loan_type'] ?? null) === 'group')
                                    <div class="mt-1 text-xs font-normal text-slate-500 dark:text-zinc-400">
                                        {{ __('application_reports.group_members') }}:
                                        @if(! empty($row['members']))
                                            {{ implode(', ', $row['members']) }}
                                        @else
                                            {{ __('common.na') }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>{{ format_tzs($row['amount_requested']) }}</td>
                            <td>{{ format_tzs($row['amount_disbursed']) }}</td>
                            <td class="font-semibold text-amber-700 dark:text-amber-400">{{ format_tzs($row['outstanding']) }}</td>
                            <td class="font-semibold text-indigo-600 dark:text-indigo-400">{{ format_tzs($row['amount_repaid']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="app-table-empty">{{ __('application_reports.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $rows->links() }}</div>
        </div>
    @endif
</div>
@endsection
