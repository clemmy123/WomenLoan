@extends('layouts.app')

@section('title', $listStatus === 'inactive' ? __('admin.deactivated_users') : __('nav.users'))

@section('content')
@php
    $isInactiveList = $listStatus === 'inactive';
    $listRoute = $isInactiveList ? route('admin.users.inactive') : route('admin.users.index');
    $f = $filters ?? [];
    $exportQuery = array_filter([
        'search' => ($search ?? null) ?: null,
        'role' => ($role ?? null) ?: null,
        'region_id' => ($f['region_id'] ?? null) ?: null,
        'district_id' => ($f['district_id'] ?? null) ?: null,
        'council_id' => ($f['council_id'] ?? null) ?: null,
        'ward_id' => ($f['ward_id'] ?? null) ?: null,
        'list' => $listStatus,
    ]);
    $userFiltersBoot = [
        'selectedRegion' => (string) ($f['region_id'] ?? ''),
        'selectedDistrict' => (string) ($f['district_id'] ?? ''),
        'selectedCouncil' => (string) ($f['council_id'] ?? ''),
        'selectedWard' => (string) ($f['ward_id'] ?? ''),
        'filtersOpen' => (bool) ($filtersApplied ?? false),
        'includeStreet' => false,
        'hasFiscalYear' => false,
        'hasPeriod' => false,
        'hasDates' => false,
        'hasSort' => false,
        'geoApi' => [
            'districts' => url('/api/loans/districts'),
            'councils' => url('/api/loans/councils'),
            'wards' => url('/api/loans/wards'),
        ],
        'locks' => ($geoBounds ?? [])['lock'] ?? [],
    ];
@endphp
<div
    class="page"
    x-data="{
        modal: {{ ($errors->has('deactivation_reason') && session('deactivate_user')) ? "'deactivate'" : 'null' }},
        deactivateUser: {{ \Illuminate\Support\Js::from(session('deactivate_user')) }},
        openDeactivate(detail) {
            this.deactivateUser = detail;
            this.modal = 'deactivate';
        },
    }"
    @user-deactivate.window="openDeactivate($event.detail)"
>
    @include('partials.page-header', [
        'title' => $isInactiveList ? __('admin.deactivated_users') : __('nav.users'),
        'subtitle' => $isInactiveList ? __('admin.deactivated_users_subtitle') : __('admin.users_subtitle'),
        'actions' => ($isInactiveList
                ? ''
                : '<a href="'.e(route('admin.users.create')).'" class="app-btn app-btn-primary">+ '.e(__('admin.new_user')).'</a>'
            )
            .view('partials.report-export-buttons', [
                'excelRoute' => route('admin.users.export.excel', $exportQuery),
                'pdfRoute' => route('admin.users.export.pdf', $exportQuery),
                'excelLabel' => __('admin.export_excel'),
                'pdfLabel' => __('admin.export_pdf'),
            ])->render(),
    ])

    <form
        method="GET"
        action="{{ $listRoute }}"
        class="app-card app-card-padded mb-4 space-y-5"
        x-data="reportFilters(@js($userFiltersBoot))"
    >
        @include('partials.filters-toggle-button', [
            'title' => __('common.filter'),
            'showLabel' => __('common.show_filters'),
            'hideLabel' => __('common.hide_filters'),
        ])

        <div class="dashboard-recent-toolbar-row list-filters-toolbar-controls">
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="{{ __('admin.users_search_placeholder') }}"
                class="dashboard-recent-input"
                autocomplete="off"
            >
        </div>

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
                    'allowAllRegions' => empty(($geoBounds ?? [])['lock']['region_id'] ?? null),
                ])

                <div class="wizard-field">
                    <label class="app-label" for="role">{{ __('common.roles') }}</label>
                    <select name="role" id="role" class="app-select">
                        @foreach($roleOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) $role === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="app-btn app-btn-primary">{{ __('common.apply_filters') }}</button>
            @if($filtersApplied ?? false)
                <a href="{{ $listRoute }}" class="app-btn app-btn-secondary">{{ __('common.clear') }}</a>
            @endif
        </div>
    </form>

<div class="app-card">
    <div class="overflow-x-auto">
    <table class="app-table">
        <thead>
            <tr>
                <th>{{ __('admin.check_number') }}</th>
                <th>{{ __('common.name') }}</th>
                <th>{{ __('common.email') }}</th>
                <th>{{ __('common.roles') }}</th>
                <th>{{ __('admin.zone_name') }}</th>
                <th>{{ __('common.status') }}</th>
                <th class="text-right">{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td class="font-mono text-xs text-slate-600">{{ $user->check_number ?: '—' }}</td>
                <td class="font-medium">{{ $user->name }}</td>
                <td class="text-slate-600">{{ $user->email }}</td>
                <td>
                    @foreach($user->roles as $roleItem)
                        @include('partials.badge', ['variant' => 'primary', 'text' => role_label($roleItem->name), 'class' => 'mr-1 mb-1'])
                    @endforeach
                </td>
                <td class="text-sm text-slate-600">
                    @if($user->zoneable)
                        <span class="block text-xs text-slate-400">{{ \App\Support\StaffZone::typeLabelForUser($user) }}</span>
                        {{ $user->zoneable->name }}
                    @else
                        {{ \App\Support\StaffZone::emptyZoneTypeLabel($user->roles->pluck('name')) }}
                    @endif
                </td>
                <td>
                    @include('partials.badge', [
                        'variant' => active_status_badge_variant($user->is_active),
                        'text' => $user->is_active ? __('common.active') : __('common.inactive'),
                    ])
                </td>
                <td class="text-right">
                    <div class="inline-flex items-center justify-end">
                        @include('partials.user-row-actions', ['user' => $user])
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="app-table-empty">
                    {{ $isInactiveList ? __('admin.no_deactivated_users') : __('admin.no_users') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="app-card-footer">{{ $users->links() }}</div>
</div>

    @include('partials.modal', [
        'name' => 'deactivate',
        'title' => __('admin.deactivate_user_title'),
        'message' => __('admin.deactivate_user_message'),
        'body' => view('admin.users._deactivate_modal_body')->render(),
    ])
</div>
@endsection
