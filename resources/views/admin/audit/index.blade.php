@extends('layouts.app')

@section('title', __('nav.audit_logs'))

@section('content')
@php
    $f = $filters;
    $hasActiveFilters = filled($f['search'] ?? null)
        || filled($f['event'] ?? null)
        || filled($f['date_from'] ?? null)
        || filled($f['date_to'] ?? null);
@endphp
<div class="page" x-data="{ filtersOpen: {{ $hasActiveFilters ? 'true' : 'false' }} }">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('nav.audit_logs') }}</h1>
            <p class="page-subtitle">{{ __('audit.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @include('partials.report-export-buttons', [
                'excelRoute' => route('admin.audit.export.excel', request()->query()),
                'pdfRoute' => route('admin.audit.export.pdf', request()->query()),
                'excelLabel' => __('audit.export_excel'),
                'pdfLabel' => __('audit.export_pdf'),
            ])
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit.index') }}" class="app-card app-card-padded space-y-5">
        <button
            type="button"
            @click="filtersOpen = !filtersOpen"
            class="flex w-full items-center justify-between gap-3 text-left"
            :aria-expanded="filtersOpen.toString()"
        >
            <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600">{{ __('audit.filters') }}</h2>
            <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-zinc-400">
                <span x-text="filtersOpen ? @js(__('audit.hide_filters')) : @js(__('audit.show_filters'))"></span>
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
                <div class="wizard-field lg:col-span-2">
                    <label class="app-label" for="search">{{ __('audit.search') }}</label>
                    <input
                        type="search"
                        name="search"
                        id="search"
                        value="{{ $f['search'] }}"
                        class="app-input"
                        placeholder="{{ __('audit.search_placeholder') }}"
                    >
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="event">{{ __('audit.event') }}</label>
                    <select name="event" id="event" class="app-select">
                        <option value="">{{ __('audit.all_events') }}</option>
                        @foreach(['created', 'updated', 'deleted', 'login', 'logout'] as $event)
                            <option value="{{ $event }}" @selected(($f['event'] ?? '') === $event)>{{ __('audit.events.'.$event) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_from">{{ __('audit.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $f['date_from'] ?? '' }}" class="app-input">
                </div>
                <div class="wizard-field">
                    <label class="app-label" for="date_to">{{ __('audit.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $f['date_to'] ?? '' }}" class="app-input">
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="app-btn app-btn-primary">{{ __('audit.apply_filters') }}</button>
                <a href="{{ route('admin.audit.index') }}" class="app-btn app-btn-secondary">{{ __('audit.reset_filters') }}</a>
            </div>
        </div>
    </form>

    <div class="app-card overflow-hidden">
        <div class="app-card-header flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-bold text-slate-900 dark:text-white">{{ __('audit.list_title') }}</h2>
            <p class="text-sm text-slate-500 dark:text-zinc-400">
                {{ __('audit.showing_count', ['count' => number_format($activities->total())]) }}
            </p>
        </div>
        <table class="app-table">
            <thead>
                <tr>
                    <th>{{ __('audit.when') }}</th>
                    <th>{{ __('audit.who') }}</th>
                    <th>{{ __('audit.event') }}</th>
                    <th>{{ __('audit.what') }}</th>
                    <th>{{ __('audit.description') }}</th>
                    <th class="text-right">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td class="whitespace-nowrap text-sm text-slate-600 dark:text-zinc-300">
                            {{ $activity->created_at?->timezone(config('app.timezone'))->format('d M Y, h:i:s A') }}
                        </td>
                        <td class="text-sm">{{ $audits->causerLabel($activity) }}</td>
                        <td>
                            @include('partials.badge', [
                                'variant' => match ($activity->event) {
                                    'created', 'login' => 'success',
                                    'deleted', 'logout' => 'danger',
                                    default => 'primary',
                                },
                                'text' => $audits->eventLabel($activity->event),
                            ])
                        </td>
                        <td class="text-sm font-medium">{{ $audits->subjectLabel($activity) }}</td>
                        <td class="text-sm text-slate-600 dark:text-zinc-300">{{ $activity->description }}</td>
                        <td class="text-right">
                            <div class="inline-flex items-center justify-end">
                                @include('partials.table-icon', [
                                    'action' => 'view',
                                    'href' => route('admin.audit.show', $activity),
                                    'label' => __('common.view'),
                                ])
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="app-table-empty">{{ __('audit.no_records') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="app-card-footer">{{ $activities->links() }}</div>
    </div>
</div>
@endsection
