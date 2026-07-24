@extends('layouts.app')

@section('title', __('by_monthly_reports.title'))

@section('content')
@php
    $f = $filters;
    $reportFiltersBoot = [
        'selectedPrimary' => (string) ($f['month'] ?? ''),
        'filtersOpen' => false,
        'revealTimeFilters' => (bool) $filtersApplied,
        'primarySelectId' => 'month',
        'hasFiscalYear' => false,
        'hasPeriod' => false,
        'hasDates' => false,
        'hasSort' => false,
    ];
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('by_monthly_reports.title') }}</h1>
            <p class="page-subtitle">{{ __('by_monthly_reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($filtersApplied)
                @include('partials.report-export-buttons', [
                    'excelRoute' => route('reports.by-monthly.export.excel', request()->query()),
                    'pdfRoute' => route('reports.by-monthly.export.pdf', request()->query()),
                    'excelLabel' => __('by_monthly_reports.export_excel'),
                    'pdfLabel' => __('by_monthly_reports.export_pdf'),
                ])
            @endif
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('reports.by-monthly.index') }}"
        class="app-card app-card-padded space-y-5"
        x-data="reportFilters(@js($reportFiltersBoot))"
    >
        @include('partials.filters-toggle-button', [
            'title' => __('by_monthly_reports.filters'),
            'showLabel' => __('by_monthly_reports.show_filters'),
            'hideLabel' => __('by_monthly_reports.hide_filters'),
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
                <div class="wizard-field">
                    <label class="app-label" for="report_year">{{ __('by_monthly_reports.year') }}</label>
                    <input
                        type="text"
                        id="report_year"
                        class="app-input"
                        value="{{ $reportYear }}"
                        readonly
                        disabled
                    >
                </div>

                <div class="wizard-field">
                    <label class="app-label" for="month">{{ __('by_monthly_reports.month') }}</label>
                    <div class="app-filter-control" :class="{ 'has-clear': selectedPrimary }">
                        <select
                            name="month"
                            id="month"
                            class="app-select"
                            x-model="selectedPrimary"
                            @change="onPrimaryChange()"
                        >
                            <option value="">{{ __('by_monthly_reports.all_months') }}</option>
                            @foreach($monthOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
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
                <button type="submit" class="app-btn app-btn-primary">{{ __('by_monthly_reports.apply_filters') }}</button>
                <a href="{{ route('reports.by-monthly.index') }}" class="app-btn app-btn-secondary">{{ __('by_monthly_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

    @if(! $filtersApplied)
        <div class="app-card app-card-padded text-center">
            <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">{{ __('by_monthly_reports.apply_filters_prompt') }}</p>
        </div>
    @else
        @php
            $collectable = (float) $summary['total_paid'] + (float) $summary['total_outstanding'];
            $collectionRate = $collectable > 0
                ? (int) min(100, round(((float) $summary['total_paid'] / $collectable) * 100))
                : 0;
            $peopleFinanced = ($summary['individual_count'] ?? 0) + ($summary['group_members_count'] ?? 0);
        @endphp

        @include('partials.repayment-summary-strip', [
            'title' => __('by_monthly_reports.summary'),
            'copy' => __('by_monthly_reports.summary_copy', [
                'count' => number_format($summary['count']),
                'year' => $reportYear,
            ]),
            'rate' => $collectionRate,
            'rateLabel' => __('reports.collection_rate', ['rate' => $collectionRate]),
            'metrics' => [
                [
                    'label' => __('by_monthly_reports.loan_count'),
                    'value' => number_format($summary['count']),
                ],
                [
                    'label' => __('by_monthly_reports.total_disbursed'),
                    'value' => format_tzs($summary['total_disbursed']),
                ],
                [
                    'label' => __('by_monthly_reports.people_financed'),
                    'value' => number_format($peopleFinanced),
                ],
                [
                    'label' => __('by_monthly_reports.group_count'),
                    'value' => number_format($summary['group_count']),
                ],
                [
                    'label' => __('by_monthly_reports.total_outstanding'),
                    'value' => format_tzs($summary['total_outstanding']),
                    'tone' => 'outstanding',
                ],
                [
                    'label' => __('by_monthly_reports.total_paid'),
                    'value' => format_tzs($summary['total_paid']),
                    'tone' => 'paid',
                ],
            ],
        ])

        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('by_monthly_reports.detail_table') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('by_monthly_reports.col_name') }}</th>
                            <th>{{ __('by_monthly_reports.col_disbursed') }}</th>
                            <th>{{ __('by_monthly_reports.col_outstanding') }}</th>
                            <th>{{ __('by_monthly_reports.col_paid') }}</th>
                            <th>{{ __('by_monthly_reports.col_phone') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="font-medium text-indigo-600 hover:underline">{{ $row['name'] }}</a>
                                <div class="text-xs text-slate-500 dark:text-zinc-400">
                                    {{ $row['loan_type_label'] }}
                                    · {{ $row['month_label'] }}
                                    · <span class="font-mono">{{ $row['track_id'] }}</span>
                                </div>
                            </td>
                            <td>{{ format_tzs($row['disbursed']) }}</td>
                            <td class="font-semibold text-amber-700 dark:text-amber-400">{{ format_tzs($row['outstanding']) }}</td>
                            <td>{{ format_tzs($row['paid']) }}</td>
                            <td>{{ $row['phone'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="app-table-empty">{{ __('by_monthly_reports.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $rows->links() }}</div>
        </div>
    @endif
</div>
@endsection
