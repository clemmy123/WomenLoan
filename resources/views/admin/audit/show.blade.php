@extends('layouts.app')

@section('title', __('audit.detail_title'))

@section('content')
@php
    $properties = $activity->properties?->toArray() ?? [];
    $old = $properties['old'] ?? [];
    $newValues = $properties['attributes'] ?? [];
    $changedKeys = collect(array_unique(array_merge(array_keys($old), array_keys($newValues))))->sort()->values();
@endphp
<div class="page">
    @include('partials.page-header', [
        'title' => __('audit.detail_title'),
        'subtitle' => __('audit.detail_subtitle'),
        'actions' => '<a href="'.e(route('admin.audit.index')).'" class="app-btn app-btn-secondary">← '.e(__('common.back')).'</a>',
    ])

    <div class="app-card app-card-padded space-y-4">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('audit.when') }}</dt>
                <dd class="mt-1 font-medium">{{ $activity->created_at ? format_app_datetime($activity->created_at, withSeconds: true) : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('audit.who') }}</dt>
                <dd class="mt-1 font-medium">{{ $audits->causerLabel($activity) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('audit.event') }}</dt>
                <dd class="mt-1">
                    @include('partials.badge', [
                        'variant' => match ($activity->event) {
                            'created' => 'success',
                            'deleted' => 'danger',
                            default => 'primary',
                        },
                        'text' => $audits->eventLabel($activity->event),
                    ])
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('audit.what') }}</dt>
                <dd class="mt-1 font-medium">{{ $audits->subjectLabel($activity) }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('audit.description') }}</dt>
                <dd class="mt-1">{{ $activity->description }}</dd>
            </div>
        </dl>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h2 class="font-bold text-slate-900 dark:text-white">{{ __('audit.changes') }}</h2>
        </div>
        @if($changedKeys->isEmpty())
            <p class="app-card-padded text-sm text-slate-500">{{ __('audit.no_changes') }}</p>
        @else
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('audit.field') }}</th>
                        <th>{{ __('audit.old_value') }}</th>
                        <th>{{ __('audit.new_value') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($changedKeys as $key)
                        <tr>
                            <td class="font-mono text-xs">{{ $key }}</td>
                            <td class="text-sm text-slate-600 dark:text-zinc-300 break-all">
                                {{ is_array($old[$key] ?? null) ? json_encode($old[$key]) : ($old[$key] ?? '—') }}
                            </td>
                            <td class="text-sm font-medium break-all">
                                {{ is_array($newValues[$key] ?? null) ? json_encode($newValues[$key]) : ($newValues[$key] ?? '—') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
