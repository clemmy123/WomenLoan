@extends('layouts.app')

@section('title', __('admin.view_user'))

@section('content')
@php
    $zoneType = \App\Support\StaffZone::typeLabelForUser($user);
    $zoneName = $user->zoneable?->name ?? '—';
@endphp

<div class="page">
    @include('partials.page-header', [
        'title' => __('admin.view_user'),
        'subtitle' => $user->name,
        'actions' => '
            <a href="'.e(route('admin.users.index')).'" class="app-btn app-btn-secondary">'.e(__('common.back')).'</a>
            <a href="'.e(route('admin.users.edit', $user)).'" class="app-btn app-btn-primary">'.e(__('common.edit')).'</a>
            <a href="'.e(route('admin.users.assign-roles', $user)).'" class="app-btn app-btn-secondary">'.e(__('admin.assign_roles')).'</a>
        ',
    ])

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="app-card app-card-padded space-y-4">
            <h3 class="font-bold text-slate-900 dark:text-white">{{ __('admin.account_details') }}</h3>

            <dl class="app-detail-list">
                <div>
                    <dt>{{ __('admin.check_number') }}</dt>
                    <dd class="font-mono">{{ $user->check_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('common.name') }}</dt>
                    <dd>{{ $user->name }}</dd>
                </div>
                <div>
                    <dt>{{ __('common.email') }}</dt>
                    <dd>{{ $user->email }}</dd>
                </div>
                <div>
                    <dt>{{ __('common.phone') }}</dt>
                    <dd>{{ $user->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('common.status') }}</dt>
                    <dd>
                        @include('partials.badge', [
                            'variant' => active_status_badge_variant($user->is_active),
                            'text' => $user->is_active ? __('common.active') : __('common.inactive'),
                        ])
                    </dd>
                </div>
            </dl>

            @if (! $user->is_active && can_view_deactivation_reason() && filled($user->deactivation_reason))
                <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4 space-y-3">
                    <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100">{{ __('admin.deactivation_details') }}</h4>
                    <dl class="app-detail-list">
                        <div>
                            <dt>{{ __('admin.deactivation_reason') }}</dt>
                            <dd class="whitespace-pre-wrap">{{ $user->deactivation_reason }}</dd>
                        </div>
                        @if ($user->deactivated_at)
                            <div>
                                <dt>{{ __('admin.deactivated_at') }}</dt>
                                <dd>{{ format_app_datetime($user->deactivated_at) }}</dd>
                            </div>
                        @endif
                        @if ($user->deactivatedBy)
                            <div>
                                <dt>{{ __('admin.deactivated_by') }}</dt>
                                <dd>{{ $user->deactivatedBy->name }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="app-card app-card-padded space-y-4">
                <h3 class="font-bold text-slate-900 dark:text-white">{{ __('common.roles') }}</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($user->roles as $role)
                        @include('partials.badge', ['variant' => 'primary', 'text' => role_label($role->name)])
                    @empty
                        <p class="text-sm text-slate-500">{{ __('admin.no_roles_assigned') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="app-card app-card-padded space-y-4">
                <h3 class="font-bold text-slate-900 dark:text-white">{{ __('admin.geo_zone') }}</h3>
                <dl class="app-detail-list">
                    <div>
                        <dt>{{ __('admin.zone_type') }}</dt>
                        <dd>{{ $zoneType }}</dd>
                    </div>
                    @if($user->zoneable_type)
                        <div>
                            <dt>{{ __('admin.zone_name') }}</dt>
                            <dd>{{ $zoneName }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
