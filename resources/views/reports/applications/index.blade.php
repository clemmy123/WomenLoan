@extends('layouts.app')

@section('title', __('application_reports.title'))

@section('content')
@php
    $f = $filters;
@endphp
<div class="page">
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
            <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.reports') }}</a>
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.dashboard') }}</a>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.applications.index') }}" class="app-card app-card-padded space-y-5">
        <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600">{{ __('application_reports.filters') }}</h2>

        <div class="wizard-form-grid wizard-form-grid-2 lg:grid-cols-4">
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
                <select name="period" id="period" class="app-select">
                    @foreach(['daily','weekly','monthly','quarterly','annually'] as $period)
                        <option value="{{ $period }}" @selected($f['period'] === $period)>{{ __('reports.period_'.$period) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="wizard-field">
                <label class="app-label" for="date_from">{{ __('application_reports.date_from') }}</label>
                <input type="date" name="date_from" id="date_from" value="{{ $f['date_from'] }}" class="app-input">
            </div>
            <div class="wizard-field">
                <label class="app-label" for="date_to">{{ __('application_reports.date_to') }}</label>
                <input type="date" name="date_to" id="date_to" value="{{ $f['date_to'] }}" class="app-input">
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="app-btn app-btn-primary">{{ __('application_reports.apply_filters') }}</button>
            <a href="{{ route('reports.applications.index') }}" class="app-btn app-btn-secondary">{{ __('application_reports.reset_filters') }}</a>
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
