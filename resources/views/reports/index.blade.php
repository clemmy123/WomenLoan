@extends('layouts.app')

@section('title', __('reports.title'))

@section('content')
@php
    $f = $filters;
@endphp
@php
    $currentFy = app(\App\Services\ReportService::class)->currentFiscalYearKey();
    $hasActiveFilters = filled(request('region_id'))
        || filled(request('district_id'))
        || filled(request('council_id'))
        || filled(request('ward_id'))
        || filled(request('street_id'))
        || filled(request('loan_type'))
        || filled(request('age_min'))
        || filled(request('age_max'))
        || filled(request('has_disability'))
        || filled(request('marital_status'))
        || filled(request('date_from'))
        || filled(request('date_to'))
        || (filled(request('period')) && request('period') !== 'annually')
        || (filled(request('fiscal_year')) && request('fiscal_year') !== $currentFy);
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('reports.title') }}</h1>
            <p class="page-subtitle">{{ __('reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @include('partials.report-export-buttons', [
                'excelRoute' => route('reports.export.excel', request()->query()),
                'pdfRoute' => route('reports.export.pdf', request()->query()),
            ])
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('reports.index') }}"
        class="app-card app-card-padded space-y-5"
        x-data="reportFilters(@js([
            'selectedRegion' => (string) ($f['region_id'] ?? ''),
            'selectedDistrict' => (string) ($f['district_id'] ?? ''),
            'selectedCouncil' => (string) ($f['council_id'] ?? ''),
            'selectedWard' => (string) ($f['ward_id'] ?? ''),
            'selectedStreet' => (string) ($f['street_id'] ?? ''),
            'filtersOpen' => $hasActiveFilters,
            'geoApi' => \App\Services\GeoHierarchyService::apiUrls(),
            'locks' => (($geoBounds ?? [])['lock'] ?? []),
        ]))"
    >
        @include('partials.filters-toggle-button', [
            'title' => __('reports.filters'),
            'showLabel' => __('reports.show_filters'),
            'hideLabel' => __('reports.hide_filters'),
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
                    <label class="app-label" for="fiscal_year">{{ __('reports.fiscal_year') }}</label>
                    <select name="fiscal_year" id="fiscal_year" class="app-select" onchange="document.getElementById('use_custom_dates').value=''">
                        @foreach($fiscalYearOptions as $fyKey => $fyLabel)
                            <option value="{{ $fyKey }}" @selected(($f['fiscal_year'] ?? '') === $fyKey)>{{ $fyLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="period">{{ __('reports.period') }}</label>
                    <select name="period" id="period" class="app-select" onchange="document.getElementById('use_custom_dates').value=''">
                        @foreach(\App\Services\ReportService::PERIODS as $period)
                            <option value="{{ $period }}" @selected($f['period'] === $period)>{{ __('reports.period_'.$period) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_from">{{ __('reports.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $f['date_from'] ?? '' }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_to">{{ __('reports.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $f['date_to'] ?? '' }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'">
                    <input type="hidden" name="use_custom_dates" id="use_custom_dates" value="{{ ($f['use_custom_dates'] ?? null) === '1' ? '1' : '' }}">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="loan_type">{{ __('reports.loan_type') }}</label>
                    <select name="loan_type" id="loan_type" class="app-select">
                        <option value="">{{ __('reports.all_types') }}</option>
                        <option value="individual" @selected($f['loan_type'] === 'individual')>{{ __('loans.types.individual') }}</option>
                        <option value="group" @selected($f['loan_type'] === 'group')>{{ __('loans.types.group') }}</option>
                    </select>
                </div>
                @include('partials.report-geo-filters')
                <div class="wizard-field">
                    <label class="app-label" for="age_min">{{ __('reports.age_min') }}</label>
                    <input type="number" min="18" max="100" name="age_min" id="age_min" value="{{ $f['age_min'] }}" class="app-input">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="age_max">{{ __('reports.age_max') }}</label>
                    <input type="number" min="18" max="100" name="age_max" id="age_max" value="{{ $f['age_max'] }}" class="app-input">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="has_disability">{{ __('reports.disability') }}</label>
                    <select name="has_disability" id="has_disability" class="app-select">
                        <option value="">{{ __('reports.all') }}</option>
                        <option value="1" @selected((string) $f['has_disability'] === '1')>{{ __('reports.yes') }}</option>
                        <option value="0" @selected((string) $f['has_disability'] === '0')>{{ __('reports.no') }}</option>
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="marital_status">{{ __('reports.marital_status') }}</label>
                    <select name="marital_status" id="marital_status" class="app-select">
                        <option value="">{{ __('reports.all') }}</option>
                        @foreach(\App\Models\Applicant::MARITAL_STATUSES as $status)
                            <option value="{{ $status }}" @selected(($f['marital_status'] ?? '') === $status)>{{ __('applicants.marital_statuses.'.$status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('reports.apply_filters') }}</button>
                <a href="{{ route('reports.index') }}" class="app-btn app-btn-secondary">{{ __('reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

    @php
        $collectable = (float) $summary['total_paid'] + (float) $summary['total_outstanding'];
        $collectionRate = $collectable > 0
            ? (int) min(100, round(((float) $summary['total_paid'] / $collectable) * 100))
            : 0;
    @endphp

    @include('partials.repayment-summary-strip', [
        'title' => __('reports.summary_title'),
        'copy' => __('reports.summary_copy', [
            'count' => number_format($summary['count']),
        ]),
        'rate' => $collectionRate,
        'rateLabel' => __('reports.collection_rate', ['rate' => $collectionRate]),
        'metrics' => [
            [
                'label' => __('reports.total_disbursed'),
                'value' => format_tzs($summary['total_disbursed']),
            ],
            [
                'label' => __('reports.total_paid'),
                'value' => format_tzs($summary['total_paid']),
                'tone' => 'paid',
            ],
            [
                'label' => __('reports.total_outstanding'),
                'value' => format_tzs($summary['total_outstanding']),
                'tone' => 'outstanding',
            ],
        ],
    ])

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6 lg:col-span-2">
            <h2 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('reports.financial_trend') }}</h2>
            <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4">{{ __('reports.legend_disbursed') }} · {{ __('reports.legend_paid') }}</p>
            <div class="h-72"><canvas id="financialTrendChart"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('reports.outstanding_by_region') }}</h2>
            <div class="h-64"><canvas id="regionChart"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('reports.loan_type_chart') }}</h2>
            <div class="h-64 flex items-center justify-center"><canvas id="loanTypeChart"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('reports.disability_chart') }}</h2>
            <div class="h-64 flex items-center justify-center"><canvas id="disabilityChart"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('reports.marital_status_chart') }}</h2>
            <div class="h-64 flex items-center justify-center"><canvas id="maritalStatusChart"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6 lg:col-span-2">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('reports.age_chart') }}</h2>
            <div class="h-64"><canvas id="ageChart"></canvas></div>
        </div>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h2 class="font-bold text-slate-900 dark:text-white">{{ __('reports.detail_table') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('reports.name') }}</th>
                        <th>{{ __('dashboard.track_id') }}</th>
                        <th>{{ __('common.region') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('reports.disbursed') }}</th>
                        <th>{{ __('reports.paid') }}</th>
                        <th>{{ __('reports.outstanding') }}</th>
                        <th>{{ __('dashboard.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $row['name'] }}</td>
                        <td>
                            <a href="{{ route('loan-applications.show', $row['hashid']) }}" class="font-mono text-xs font-semibold text-indigo-600 hover:underline">{{ $row['track_id'] }}</a>
                        </td>
                        <td>{{ $row['region'] ?? '—' }}</td>
                        <td>{{ $row['loan_type'] }}</td>
                        <td>{{ format_tzs($row['disbursed']) }}</td>
                        <td>{{ format_tzs($row['paid']) }}</td>
                        <td class="font-semibold text-amber-700 dark:text-amber-400">{{ format_tzs($row['outstanding']) }}</td>
                        <td class="text-slate-500 text-xs">{{ $row['date'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="app-table-empty">{{ __('reports.no_results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="app-card-footer">{{ $rows->links() }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script type="application/json" id="reports-chart-data">@json($charts)</script>
@vite(['resources/js/pages/reports.js'])
@endpush
