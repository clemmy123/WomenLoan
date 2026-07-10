@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
@php
    $f = $filters;
    $currentFy = $debtReports->currentFiscalYearKey();
    $hasActiveFilters = filled(request('region_id'))
        || filled(request('district_id'))
        || filled(request('council_id'))
        || filled(request('ward_id'))
        || filled(request('street_id'))
        || filled(request('date_from'))
        || filled(request('date_to'))
        || filled(request('search'))
        || (filled(request('sort')) && request('sort') !== 'outstanding_desc')
        || filled(request('quarter'))
        || (filled(request('period')) && request('period') !== 'annually')
        || (filled(request('fiscal_year')) && request('fiscal_year') !== $currentFy);
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ $pageTitle }}</h1>
            <p class="page-subtitle">{{ $pageSubtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @include('partials.report-export-buttons', [
                'excelRoute' => route($excelRouteName, request()->query()),
                'pdfRoute' => route($pdfRouteName, request()->query()),
                'excelLabel' => __('analytical_reports.export_excel'),
                'pdfLabel' => __('analytical_reports.export_pdf'),
            ])
            <a href="{{ route('reports.analytical.overview') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.analytical_overview') }}</a>
        </div>
    </div>

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

    <form
        method="GET"
        action="{{ route($indexRouteName) }}"
        class="app-card app-card-padded space-y-5 mt-6"
        x-data="reportFilters(@js([
            'selectedRegion' => (string) ($f['region_id'] ?? ''),
            'selectedDistrict' => (string) ($f['district_id'] ?? ''),
            'selectedCouncil' => (string) ($f['council_id'] ?? ''),
            'selectedWard' => (string) ($f['ward_id'] ?? ''),
            'selectedStreet' => (string) ($f['street_id'] ?? ''),
            'filtersOpen' => $hasActiveFilters,
            'geoApi' => \App\Services\GeoHierarchyService::apiUrls(),
        ]))"
    >
        <button
            type="button"
            @click="filtersOpen = !filtersOpen"
            class="flex w-full items-center justify-between gap-3 text-left"
            :aria-expanded="filtersOpen.toString()"
        >
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600">{{ __('analytical_reports.filters') }}</h2>
            <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-zinc-400">
                <span x-text="filtersOpen ? @js(__('analytical_reports.hide_filters')) : @js(__('analytical_reports.show_filters'))"></span>
                <svg class="h-4 w-4 transition-transform duration-200" :class="filtersOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </button>

        <div
            x-show="filtersOpen"
            x-cloak
            x-transition
            class="space-y-5"
        >
            <div class="wizard-form-grid wizard-form-grid-2 lg:grid-cols-4">
                <div class="wizard-field">
                    <label class="app-label" for="fiscal_year">{{ __('analytical_reports.fiscal_year') }}</label>
                    <select name="fiscal_year" id="fiscal_year" class="app-select">
                        @foreach($fiscalYearOptions as $fyKey => $fyLabel)
                            <option value="{{ $fyKey }}" @selected(($f['fiscal_year'] ?? '') === $fyKey)>{{ $fyLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="sort">{{ __('analytical_reports.sort_by') }}</label>
                    <select name="sort" id="sort" class="app-select">
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($f['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="search">{{ __('common.search') }}</label>
                    <input type="search" name="search" id="search" value="{{ $f['search'] ?? '' }}" class="app-input" placeholder="{{ __('analytical_reports.search_placeholder') }}">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="region_id">{{ __('geo.region') }}</label>
                    <select name="region_id" id="region_id" class="app-select" x-model="selectedRegion" @change="onRegionChange()">
                        <option value="">{{ __('geo.select_region') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="district_id">{{ __('geo.district') }}</label>
                    <select name="district_id" id="district_id" class="app-select" x-model="selectedDistrict" @change="onDistrictChange()">
                        <option value="">{{ __('geo.select_district') }}</option>
                        <template x-for="item in districts" :key="item.id">
                            <option :value="String(item.id)" x-text="item.name" :selected="String(item.id) === selectedDistrict"></option>
                        </template>
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="council_id">{{ __('geo.council') }}</label>
                    <select name="council_id" id="council_id" class="app-select" x-model="selectedCouncil" @change="onCouncilChange()">
                        <option value="">{{ __('geo.select_council') }}</option>
                        <template x-for="item in councils" :key="item.id">
                            <option :value="String(item.id)" x-text="item.name" :selected="String(item.id) === selectedCouncil"></option>
                        </template>
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="ward_id">{{ __('geo.ward') }}</label>
                    <select name="ward_id" id="ward_id" class="app-select" x-model="selectedWard" @change="onWardChange()">
                        <option value="">{{ __('geo.select_ward') }}</option>
                        <template x-for="item in wards" :key="item.id">
                            <option :value="String(item.id)" x-text="item.name" :selected="String(item.id) === selectedWard"></option>
                        </template>
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_from">{{ __('analytical_reports.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $f['date_from'] ?? '' }}" class="app-input">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_to">{{ __('analytical_reports.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $f['date_to'] ?? '' }}" class="app-input">
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="app-btn app-btn-primary">{{ __('analytical_reports.apply_filters') }}</button>
                <a href="{{ route($indexRouteName) }}" class="app-btn app-btn-secondary">{{ __('analytical_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

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
                        <th>{{ __('analytical_reports.col_elapsed') }}</th>
                        <th>{{ __('dashboard.track_id') }}</th>
                        <th class="w-24">{{ __('common.actions') }}</th>
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
                            <td>
                                <span class="font-medium">{{ $row['elapsed_label'] }}</span>
                                @if($mode === 'overdue')
                                    <div class="text-xs text-slate-500 dark:text-zinc-400">{{ __('analytical_reports.due_since', ['date' => $row['due_date']]) }}</div>
                                @endif
                            </td>
                            <td>@include('partials.track-id-chip', ['trackId' => $row['track_id']])</td>
                            <td>
                                <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="app-btn app-btn-secondary text-xs">{{ __('common.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="app-table-empty">{{ __('analytical_reports.no_debt_results') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->total())
            <div class="app-card-footer">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/reports.js'])
@endpush
