@extends('layouts.app')

@section('title', __('by_age_reports.title'))

@section('content')
@php
    $f = $filters;
    $reportFiltersBoot = [
        'selectedRegion' => (string) ($f['region_id'] ?? ''),
        'selectedDistrict' => (string) ($f['district_id'] ?? ''),
        'selectedCouncil' => (string) ($f['council_id'] ?? ''),
        'selectedWard' => (string) ($f['ward_id'] ?? ''),
        'selectedStreet' => (string) ($f['street_id'] ?? ''),
        'selectedAgeMin' => (string) ($f['age_min'] ?? ''),
        'selectedAgeMax' => (string) ($f['age_max'] ?? ''),
        'selectedSort' => (string) ($f['sort'] ?? 'newest'),
        'filtersOpen' => false,
        'revealTimeFilters' => (bool) $filtersApplied,
        'hasFiscalYear' => false,
        'hasPeriod' => false,
        'hasDates' => false,
        'hasSort' => true,
        'hasAge' => true,
        'geoApi' => [
            'districts' => url('/api/loans/districts'),
            'councils' => url('/api/loans/councils'),
            'wards' => url('/api/loans/wards'),
            'streets' => url('/api/loans/streets'),
        ],
        'locks' => ($geoBounds ?? [])['lock'] ?? [],
    ];
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('by_age_reports.title') }}</h1>
            <p class="page-subtitle">{{ __('by_age_reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($filtersApplied)
                @include('partials.report-export-buttons', [
                    'excelRoute' => route('reports.by-age.export.excel', request()->query()),
                    'pdfRoute' => route('reports.by-age.export.pdf', request()->query()),
                    'excelLabel' => __('by_age_reports.export_excel'),
                    'pdfLabel' => __('by_age_reports.export_pdf'),
                ])
            @endif
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('reports.by-age.index') }}"
        class="app-card app-card-padded space-y-5"
        x-data="reportFilters(@js($reportFiltersBoot))"
    >
        @include('partials.filters-toggle-button', [
            'title' => __('by_age_reports.filters'),
            'showLabel' => __('by_age_reports.show_filters'),
            'hideLabel' => __('by_age_reports.hide_filters'),
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
                @include('partials.report-geo-filters', [
                    'regions' => $regions,
                    'geoBounds' => $geoBounds ?? [],
                    'allowAllRegions' => true,
                ])

                <div class="wizard-field" x-show="showAgeMin" x-cloak>
                    <label class="app-label" for="age_min">{{ __('by_age_reports.age_min') }}</label>
                    <div class="app-filter-control app-filter-control--input" :class="{ 'has-clear': selectedAgeMin }">
                        <input
                            type="number"
                            name="age_min"
                            id="age_min"
                            min="0"
                            max="120"
                            class="app-input"
                            placeholder="0"
                            x-model="selectedAgeMin"
                            @change="onAgeMinChange()"
                        >
                        <button
                            type="button"
                            class="app-filter-clear-inside"
                            x-show="selectedAgeMin"
                            x-cloak
                            @click.prevent="clearAgeMin()"
                            title="{{ __('common.clear') }}"
                            aria-label="{{ __('common.clear') }}"
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="wizard-field" x-show="showAgeMax" x-cloak>
                    <label class="app-label" for="age_max">{{ __('by_age_reports.age_max') }}</label>
                    <div class="app-filter-control app-filter-control--input" :class="{ 'has-clear': selectedAgeMax }">
                        <input
                            type="number"
                            name="age_max"
                            id="age_max"
                            min="0"
                            max="120"
                            class="app-input"
                            placeholder="120"
                            x-model="selectedAgeMax"
                            @change="onAgeMaxChange()"
                        >
                        <button
                            type="button"
                            class="app-filter-clear-inside"
                            x-show="selectedAgeMax"
                            x-cloak
                            @click.prevent="clearAgeMax()"
                            title="{{ __('common.clear') }}"
                            aria-label="{{ __('common.clear') }}"
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                @include('partials.report-time-filters', [
                    'langPrefix' => 'by_age_reports',
                    'showFiscalYear' => false,
                    'showPeriod' => false,
                    'showDates' => false,
                    'sortOptions' => $sortOptions,
                ])
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('by_age_reports.apply_filters') }}</button>
                <a href="{{ route('reports.by-age.index') }}" class="app-btn app-btn-secondary">{{ __('by_age_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

    @if(! $filtersApplied)
        <div class="app-card app-card-padded text-center">
            <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">{{ __('by_age_reports.apply_filters_prompt') }}</p>
        </div>
    @else
        @php
            $collectable = (float) $summary['total_paid'] + (float) $summary['total_outstanding'];
            $collectionRate = $collectable > 0
                ? (int) min(100, round(((float) $summary['total_paid'] / $collectable) * 100))
                : 0;
            $peopleFinanced = $summary['count'] ?? 0;
        @endphp

        @include('partials.repayment-summary-strip', [
            'title' => __('by_age_reports.summary'),
            'copy' => __('by_age_reports.summary_copy', [
                'count' => number_format($summary['count']),
            ]),
            'rate' => $collectionRate,
            'rateLabel' => __('reports.collection_rate', ['rate' => $collectionRate]),
            'metrics' => [
                [
                    'label' => __('by_age_reports.total_disbursed'),
                    'value' => format_tzs($summary['total_disbursed']),
                ],
                [
                    'label' => __('by_age_reports.people_financed'),
                    'value' => number_format($peopleFinanced),
                ],
                [
                    'label' => __('by_age_reports.group_count'),
                    'value' => number_format($summary['group_count']),
                ],
                [
                    'label' => __('by_age_reports.total_outstanding'),
                    'value' => format_tzs($summary['total_outstanding']),
                    'tone' => 'outstanding',
                ],
                [
                    'label' => __('by_age_reports.total_paid'),
                    'value' => format_tzs($summary['total_paid']),
                    'tone' => 'paid',
                ],
            ],
        ])

        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('by_age_reports.detail_table') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('by_age_reports.col_name') }}</th>
                            <th>{{ __('by_age_reports.col_age') }}</th>
                            <th>{{ __('by_age_reports.col_disbursed') }}</th>
                            <th>{{ __('by_age_reports.col_outstanding') }}</th>
                            <th>{{ __('by_age_reports.col_paid') }}</th>
                            <th>{{ __('by_age_reports.col_phone') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="font-medium text-indigo-600 hover:underline">{{ $row['name'] }}</a>
                                <div class="text-xs text-slate-500 dark:text-zinc-400">
                                    {{ $row['loan_type_label'] }}
                                    · {{ $row['region'] }}
                                    · <span class="font-mono">{{ $row['track_id'] }}</span>
                                </div>
                            </td>
                            <td>{{ $row['age'] ?? __('common.na') }}</td>
                            <td>{{ format_tzs($row['disbursed']) }}</td>
                            <td class="font-semibold text-amber-700 dark:text-amber-400">{{ format_tzs($row['outstanding']) }}</td>
                            <td>{{ format_tzs($row['paid']) }}</td>
                            <td>{{ $row['phone'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="app-table-empty">{{ __('by_age_reports.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $rows->links() }}</div>
        </div>
    @endif
</div>
@endsection
