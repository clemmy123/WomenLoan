@extends('layouts.app')

@section('title', __('admin.view_user'))

@section('content')
@php
    use App\Models\Council;
    use App\Models\Region;
    use App\Models\Ward;

    $zoneType = match ($user->zoneable_type) {
        Region::class => __('admin.zone_region'),
        Council::class => __('admin.zone_council'),
        Ward::class => __('admin.zone_ward'),
        default => __('admin.zone_none'),
    };
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

    {{-- Actions zimewekwa kwenye page-header ili kuendana na standard ya UI --}}
</div>
@endsection
