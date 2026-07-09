@extends('layouts.app')

@section('title', __('application_reports.title'))

@section('content')
@php
    $f = $filters;
    $currentFy = app(\App\Services\ApplicationReportService::class)->currentFiscalYearKey();
    $hasActiveFilters = filled(request('status'))
        || filled(request('date_from'))
        || filled(request('date_to'))
        || (filled(request('period')) && request('period') !== 'annually')
        || (filled(request('fiscal_year')) && request('fiscal_year') !== $currentFy);
@endphp
<div class="page" x-data="{ filtersOpen: {{ $hasActiveFilters ? 'true' : 'false' }} }">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('application_reports.title') }}</h1>
            <p class="page-subtitle">{{ __('application_reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @include('partials.report-export-buttons', [
                'excelRoute' => route('reports.applications.export.excel', request()->query()),
                'pdfRoute' => route('reports.applications.export.pdf', request()->query()),
                'excelLabel' => __('application_reports.export_excel'),
                'pdfLabel' => __('application_reports.export_pdf'),
            ])
            <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.reports_overview') }}</a>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.applications.index') }}" class="app-card app-card-padded space-y-5">
        <button
            type="button"
            @click="filtersOpen = !filtersOpen"
            class="flex w-full items-center justify-between gap-3 text-left"
            :aria-expanded="filtersOpen.toString()"
        >
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600">{{ __('application_reports.filters') }}</h2>
            <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-zinc-400">
                <span x-text="filtersOpen ? @js(__('application_reports.hide_filters')) : @js(__('application_reports.show_filters'))"></span>
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
                    <label class="app-label" for="fiscal_year">{{ __('reports.fiscal_year') }}</label>
                    <select name="fiscal_year" id="fiscal_year" class="app-select" onchange="document.getElementById('use_custom_dates').value=''">
                        @foreach($fiscalYearOptions as $fyKey => $fyLabel)
                            <option value="{{ $fyKey }}" @selected(($f['fiscal_year'] ?? '') === $fyKey)>{{ $fyLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="status">{{ __('application_reports.status') }}</label>
                    <select name="status" id="status" class="app-select">
                        <option value="">{{ __('application_reports.all_statuses') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected($f['status'] === $status)>{{ loan_status_label($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="period">{{ __('application_reports.period') }}</label>
                    <select name="period" id="period" class="app-select" onchange="document.getElementById('use_custom_dates').value=''">
                        @foreach(\App\Services\ApplicationReportService::PERIODS as $period)
                            <option value="{{ $period }}" @selected($f['period'] === $period)>{{ __('reports.period_'.$period) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_from">{{ __('application_reports.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $f['date_from'] }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_to">{{ __('application_reports.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $f['date_to'] }}" class="app-input" onchange="document.getElementById('use_custom_dates').value='1'">
                    <input type="hidden" name="use_custom_dates" id="use_custom_dates" value="{{ ($f['use_custom_dates'] ?? null) === '1' ? '1' : '' }}">
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('application_reports.apply_filters') }}</button>
                <a href="{{ route('reports.applications.index') }}" class="app-btn app-btn-secondary">{{ __('application_reports.reset_filters') }}</a>
            </div>
        </div>
    </form>

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
                        <th>{{ __('application_reports.bank_name') }}</th>
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
                        <td class="font-medium text-slate-900 dark:text-white">{{ $row['full_name'] }}</td>
                        <td>{{ format_tzs($row['amount_requested']) }}</td>
                        <td>{{ format_tzs($row['amount_disbursed']) }}</td>
                        <td>{{ $row['bank_name'] }}</td>
                        <td class="font-semibold text-amber-700 dark:text-amber-400">{{ format_tzs($row['outstanding']) }}</td>
                        <td class="font-semibold text-indigo-600 dark:text-indigo-400">{{ format_tzs($row['amount_repaid']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="app-table-empty">{{ __('application_reports.no_results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="app-card-footer">{{ $rows->links() }}</div>
    </div>
</div>
@endsection
