@extends('layouts.app')

@section('title', __('by_region_reports.title'))

@section('content')
@php
    $f = $filters;
    $reportFiltersBoot = [
        'selectedRegion' => (string) ($f['region_id'] ?? ''),
        'selectedDistrict' => (string) ($f['district_id'] ?? ''),
        'selectedCouncil' => (string) ($f['council_id'] ?? ''),
        'selectedWard' => (string) ($f['ward_id'] ?? ''),
        'selectedStreet' => (string) ($f['street_id'] ?? ''),
        'selectedFiscalYear' => (string) ($f['fiscal_year'] ?? ''),
        'defaultFiscalYear' => (string) ($f['fiscal_year'] ?? app(\App\Services\ByRegionReportService::class)->currentFiscalYearKey()),
        'selectedPeriod' => (string) ($f['period'] ?? 'annually'),
        'selectedDateFrom' => (string) ($f['date_from'] ?? ''),
        'selectedDateTo' => (string) ($f['date_to'] ?? ''),
        'selectedSort' => (string) ($f['sort'] ?? 'newest'),
        'useCustomDates' => (($f['use_custom_dates'] ?? null) === '1') ? '1' : '',
        'filtersOpen' => false,
        'revealTimeFilters' => (bool) $filtersApplied,
        'geoApi' => [
            'districts' => url('/api/loans/districts'),
            'councils' => url('/api/loans/councils'),
            'wards' => url('/api/loans/wards'),
            'streets' => url('/api/loans/streets'),
        ],
        'locks' => ($geoBounds ?? [])['lock'] ?? [],
        'submitOnPeriodChange' => true,
    ];
@endphp
<div class="app-page">
    <form
        method="GET"
        action="{{ route('reports.by-region.index') }}"
        class="app-page-report"
        data-report-filters='@json($reportFiltersBoot)'
    >
    <div class="app-page-header">
        <div>
            <div class="app-page-heading-row">
                <h1 class="app-page-title lg:text-3xl">{{ __('by_region_reports.title') }}</h1>
                @include('partials.period-segmented', [
                    'periods' => \App\Services\ByRegionReportService::PERIODS,
                    'compact' => true,
                    'submitOnChange' => true,
                ])
            </div>
            <p class="app-page-subtitle">{{ __('by_region_reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($filtersApplied)
                @include('partials.report-export-buttons', [
                    'excelRoute' => route('reports.by-region.export.excel', request()->query()),
                    'pdfRoute' => route('reports.by-region.export.pdf', request()->query()),
                    'excelLabel' => __('by_region_reports.export_excel'),
                    'pdfLabel' => __('by_region_reports.export_pdf'),
                ])
            @endif
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <div class="app-card app-card-padded space-y-5">
        @include('partials.filters-toggle-button', [
            'title' => __('by_region_reports.filters'),
            'showLabel' => __('by_region_reports.show_filters'),
            'hideLabel' => __('by_region_reports.hide_filters'),
        ])

        <div id="filters-panel" class="collapse space-y-5">
            <div class="wizard-form-grid wizard-form-grid-2 lg:grid-cols-3">
                @include('partials.report-geo-filters', [
                    'regions' => $regions,
                    'geoBounds' => $geoBounds ?? [],
                    'allowAllRegions' => true,
                ])

                @include('partials.report-time-filters', [
                    'langPrefix' => 'by_region_reports',
                    'fiscalYearOptions' => $fiscalYearOptions,
                    'sortOptions' => $sortOptions,
                    'periods' => \App\Services\ByRegionReportService::PERIODS,
                    'showPeriod' => false,
                ])
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('by_region_reports.apply_filters') }}</button>
                <a href="{{ route('reports.by-region.index') }}" class="app-btn app-btn-secondary">{{ __('by_region_reports.reset_filters') }}</a>
            </div>
        </div>
    </div>
    </form>

    @if(! $filtersApplied)
        <div class="app-card app-card-padded text-center">
            <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">{{ __('by_region_reports.apply_filters_prompt') }}</p>
        </div>
    @else
        @php
            $collectable = (float) $summary['total_paid'] + (float) $summary['total_outstanding'];
            $collectionRate = $collectable > 0
                ? (int) min(100, round(((float) $summary['total_paid'] / $collectable) * 100))
                : 0;
        @endphp

        @include('partials.repayment-summary-strip', [
            'title' => __('by_region_reports.summary'),
            'copy' => __('by_region_reports.summary_copy', [
                'count' => number_format($summary['count']),
            ]),
            'rate' => $collectionRate,
            'rateLabel' => __('reports.collection_rate', ['rate' => $collectionRate]),
            'metrics' => [
                [
                    'label' => __('by_region_reports.total_disbursed'),
                    'value' => format_tzs($summary['total_disbursed']),
                ],
                [
                    'label' => __('by_region_reports.individual_count'),
                    'value' => number_format($summary['individual_count']),
                ],
                [
                    'label' => __('by_region_reports.group_count'),
                    'value' => number_format($summary['group_count']),
                ],
                [
                    'label' => __('by_region_reports.total_outstanding'),
                    'value' => format_tzs($summary['total_outstanding']),
                    'tone' => 'outstanding',
                ],
                [
                    'label' => __('by_region_reports.total_paid'),
                    'value' => format_tzs($summary['total_paid']),
                    'tone' => 'paid',
                ],
            ],
        ])

        @include('partials.by-region-charts')

        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('by_region_reports.detail_table') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('by_region_reports.col_name') }}</th>
                            <th>{{ __('by_region_reports.col_disbursed') }}</th>
                            <th>{{ __('by_region_reports.col_outstanding') }}</th>
                            <th>{{ __('by_region_reports.col_paid') }}</th>
                            <th>{{ __('by_region_reports.col_phone') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="font-medium text-indigo-600 hover:underline">{{ $row['name'] }}</a>
                                <div class="text-xs text-slate-500 dark:text-zinc-400">
                                    {{ $row['loan_type_label'] }}
                                    @if(!empty($row['region']))
                                        · {{ $row['region'] }}
                                    @endif
                                    · <span class="font-mono">{{ $row['track_id'] }}</span>
                                </div>
                            </td>
                            <td>{{ format_tzs($row['disbursed']) }}</td>
                            <td class="font-semibold text-amber-700 dark:text-amber-400">{{ format_tzs($row['outstanding']) }}</td>
                            <td>{{ format_tzs($row['paid']) }}</td>
                            <td>{{ $row['phone'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="app-table-empty">{{ __('by_region_reports.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $rows->links() }}</div>
        </div>
    @endif
</div>
@endsection
