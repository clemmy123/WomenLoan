@extends('layouts.app')

@section('title', __('analytical_reports.overview_title'))

@section('content')
@php
    $f = $filters;
    $currentFy = app(\App\Services\AnalyticalReportService::class)->currentFiscalYearKey();
    $hasActiveFilters = filled(request('region_id'))
        || filled(request('district_id'))
        || filled(request('council_id'))
        || filled(request('ward_id'))
        || filled(request('street_id'))
        || filled(request('date_from'))
        || filled(request('date_to'))
        || (filled(request('sort')) && request('sort') !== 'newest')
        || filled(request('quarter'))
        || (filled(request('period')) && request('period') !== 'annually')
        || (filled(request('fiscal_year')) && request('fiscal_year') !== $currentFy);
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('analytical_reports.overview_title') }}</h1>
            <p class="page-subtitle">{{ __('analytical_reports.overview_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @include('partials.report-export-buttons', [
                'excelRoute' => route('reports.analytical.export.excel', request()->query()),
                'pdfRoute' => route('reports.analytical.export.pdf', request()->query()),
                'excelLabel' => __('analytical_reports.export_excel'),
                'pdfLabel' => __('analytical_reports.export_pdf'),
            ])
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('reports.analytical.overview') }}"
        class="app-card app-card-padded space-y-5"
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
                    <select name="fiscal_year" id="fiscal_year" class="app-select" onchange="document.getElementById('use_custom_dates').value=''; document.getElementById('quarter').value=''">
                        @foreach($fiscalYearOptions as $fyKey => $fyLabel)
                            <option value="{{ $fyKey }}" @selected(($f['fiscal_year'] ?? '') === $fyKey)>{{ $fyLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="period">{{ __('analytical_reports.period') }}</label>
                    <select name="period" id="period" class="app-select" onchange="document.getElementById('use_custom_dates').value=''; document.getElementById('quarter').value=''">
                        @foreach(\App\Services\AnalyticalReportService::PERIODS as $period)
                            <option value="{{ $period }}" @selected(empty($f['quarter']) && $f['period'] === $period)>{{ __('analytical_reports.period_'.$period) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="quarter">{{ __('analytical_reports.quarter') }}</label>
                    <select name="quarter" id="quarter" class="app-select" onchange="if (this.value) { document.getElementById('use_custom_dates').value=''; }">
                        <option value="">{{ __('analytical_reports.quarter_none') }}</option>
                        @foreach(\App\Services\AnalyticalReportService::QUARTERS as $quarter)
                            <option value="{{ $quarter }}" @selected(($f['quarter'] ?? null) === $quarter)>{{ __('analytical_reports.period_'.$quarter) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_from">{{ __('analytical_reports.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $f['date_from'] ?? '' }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'; document.getElementById('quarter').value=''">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_to">{{ __('analytical_reports.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $f['date_to'] ?? '' }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'; document.getElementById('quarter').value=''">
                    <input type="hidden" name="use_custom_dates" id="use_custom_dates" value="{{ ($f['use_custom_dates'] ?? null) === '1' ? '1' : '' }}">
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
                    <label class="app-label" for="region_id">{{ __('geo.region') }}</label>
                    <select name="region_id" id="region_id" x-model="selectedRegion" @change="onRegionChange()" class="app-select">
                        <option value="">{{ __('geo.select_region') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="district_id">{{ __('geo.district') }}</label>
                    <select name="district_id" id="district_id" x-model="selectedDistrict" @change="onDistrictChange()" class="app-select">
                        <option value="">{{ __('geo.select_district') }}</option>
                        <template x-for="district in districts" :key="district.id">
                            <option :value="String(district.id)" x-text="district.name"></option>
                        </template>
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="council_id">{{ __('geo.council') }}</label>
                    <select name="council_id" id="council_id" x-model="selectedCouncil" @change="onCouncilChange()" class="app-select">
                        <option value="">{{ __('geo.select_council') }}</option>
                        <template x-for="council in councils" :key="council.id">
                            <option :value="String(council.id)" x-text="council.name"></option>
                        </template>
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="ward_id">{{ __('geo.ward') }}</label>
                    <select name="ward_id" id="ward_id" x-model="selectedWard" @change="onWardChange()" class="app-select">
                        <option value="">{{ __('geo.select_ward') }}</option>
                        <template x-for="ward in wards" :key="ward.id">
                            <option :value="String(ward.id)" x-text="ward.name"></option>
                        </template>
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="street_id">{{ __('geo.street') }}</label>
                    <select name="street_id" id="street_id" x-model="selectedStreet" class="app-select">
                        <option value="">{{ __('geo.select_street') }}</option>
                        <template x-for="street in streets" :key="street.id">
                            <option :value="String(street.id)" x-text="street.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('analytical_reports.apply_filters') }}</button>
                <a href="{{ route('reports.analytical.overview') }}" class="app-btn app-btn-secondary">{{ __('analytical_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

    <section class="analytical-hero">
        <div class="analytical-hero-head">
            <div>
                <h2 class="analytical-hero-title">{{ __('analytical_reports.summary') }}</h2>
                <p class="analytical-hero-subtitle">
                    {{ __('analytical_reports.fiscal_year') }}:
                    {{ $f['fiscal_year'] === \App\Support\FiscalYear::ALL_KEY ? __('analytical_reports.all_years') : $f['fiscal_year'] }}
                    ·
                    @if(!empty($f['quarter']))
                        {{ __('analytical_reports.quarter') }}:
                        {{ __('analytical_reports.period_'.$f['quarter']) }}
                    @else
                        {{ __('analytical_reports.period') }}:
                        {{ __('analytical_reports.period_'.$f['period']) }}
                    @endif
                    @if($f['date_from'] || $f['date_to'])
                        · {{ $f['date_from'] ?? '—' }} → {{ $f['date_to'] ?? '—' }}
                    @else
                        · {{ __('analytical_reports.all_years') }}
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
</div>
@endsection

@push('scripts')
<script type="application/json" id="analytical-chart-data">@json($charts)</script>
@vite(['resources/js/pages/analytical-reports.js'])
@endpush
