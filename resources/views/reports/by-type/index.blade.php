@extends('layouts.app')

@section('title', __('by_type_reports.title'))

@section('content')
@php
    $f = $filters;
@endphp
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('by_type_reports.title') }}</h1>
            <p class="page-subtitle">{{ __('by_type_reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($filtersApplied)
                @include('partials.report-export-buttons', [
                    'excelRoute' => route('reports.by-type.export.excel', request()->query()),
                    'pdfRoute' => route('reports.by-type.export.pdf', request()->query()),
                    'excelLabel' => __('by_type_reports.export_excel'),
                    'pdfLabel' => __('by_type_reports.export_pdf'),
                ])
            @endif
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('reports.by-type.index') }}"
        class="app-card app-card-padded space-y-5"
        x-data="{ filtersOpen: false }"
    >
        @include('partials.filters-toggle-button', [
            'title' => __('by_type_reports.filters'),
            'showLabel' => __('by_type_reports.show_filters'),
            'hideLabel' => __('by_type_reports.hide_filters'),
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
                    <label class="app-label" for="loan_type">{{ __('by_type_reports.loan_type') }}</label>
                    <select name="loan_type" id="loan_type" class="app-select">
                        <option value="" @selected(empty($f['loan_type']))>{{ __('by_type_reports.all_types') }}</option>
                        <option value="individual" @selected(($f['loan_type'] ?? '') === 'individual')>{{ __('loans.types.individual') }}</option>
                        <option value="group" @selected(($f['loan_type'] ?? '') === 'group')>{{ __('loans.types.group') }}</option>
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="fiscal_year">{{ __('by_type_reports.fiscal_year') }}</label>
                    <select name="fiscal_year" id="fiscal_year" class="app-select" onchange="document.getElementById('use_custom_dates').value=''">
                        @foreach($fiscalYearOptions as $fyKey => $fyLabel)
                            <option value="{{ $fyKey }}" @selected(($f['fiscal_year'] ?? '') === $fyKey)>{{ $fyLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="period">{{ __('by_type_reports.period') }}</label>
                    <select name="period" id="period" class="app-select" onchange="document.getElementById('use_custom_dates').value=''">
                        @foreach(\App\Services\ByTypeReportService::PERIODS as $period)
                            <option value="{{ $period }}" @selected(($f['period'] ?? '') === $period)>{{ __('reports.period_'.$period) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_from">{{ __('by_type_reports.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $f['date_from'] ?? '' }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_to">{{ __('by_type_reports.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $f['date_to'] ?? '' }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'">
                    <input type="hidden" name="use_custom_dates" id="use_custom_dates" value="{{ ($f['use_custom_dates'] ?? null) === '1' ? '1' : '' }}">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="sort">{{ __('by_type_reports.sort_by') }}</label>
                    <select name="sort" id="sort" class="app-select">
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($f['sort'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('by_type_reports.apply_filters') }}</button>
                <a href="{{ route('reports.by-type.index') }}" class="app-btn app-btn-secondary">{{ __('by_type_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

    @if(! $filtersApplied)
        <div class="app-card app-card-padded text-center">
            <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">{{ __('by_type_reports.apply_filters_prompt') }}</p>
        </div>
    @else
        @php
            $collectable = (float) $summary['total_paid'] + (float) $summary['total_outstanding'];
            $collectionRate = $collectable > 0
                ? (int) min(100, round(((float) $summary['total_paid'] / $collectable) * 100))
                : 0;
            $loanTypeFilter = $f['loan_type'] ?? null;
            $peopleMetric = match ($loanTypeFilter) {
                'group' => [
                    'label' => __('by_type_reports.people_financed'),
                    'value' => number_format($summary['group_members_count']),
                ],
                'individual' => [
                    'label' => __('by_type_reports.individual_count'),
                    'value' => number_format($summary['individual_count']),
                ],
                default => [
                    'label' => __('by_type_reports.people_financed'),
                    'value' => number_format($summary['individual_count'] + $summary['group_members_count']),
                ],
            };
        @endphp

        @include('partials.repayment-summary-strip', [
            'title' => __('by_type_reports.summary'),
            'copy' => __('by_type_reports.summary_copy', [
                'count' => number_format($summary['count']),
            ]),
            'rate' => $collectionRate,
            'rateLabel' => __('reports.collection_rate', ['rate' => $collectionRate]),
            'metrics' => [
                [
                    'label' => __('by_type_reports.total_disbursed'),
                    'value' => format_tzs($summary['total_disbursed']),
                ],
                $peopleMetric,
                [
                    'label' => __('by_type_reports.group_count'),
                    'value' => number_format($summary['group_count']),
                ],
                [
                    'label' => __('by_type_reports.total_outstanding'),
                    'value' => format_tzs($summary['total_outstanding']),
                    'tone' => 'outstanding',
                ],
                [
                    'label' => __('by_type_reports.total_paid'),
                    'value' => format_tzs($summary['total_paid']),
                    'tone' => 'paid',
                ],
            ],
        ])

        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('by_type_reports.detail_table') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('by_type_reports.col_name') }}</th>
                            <th>{{ __('by_type_reports.col_disbursed') }}</th>
                            <th>{{ __('by_type_reports.col_outstanding') }}</th>
                            <th>{{ __('by_type_reports.col_paid') }}</th>
                            <th>{{ __('by_type_reports.col_phone') }}</th>
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
                        <tr><td colspan="5" class="app-table-empty">{{ __('by_type_reports.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $rows->links() }}</div>
        </div>
    @endif
</div>
@endsection
