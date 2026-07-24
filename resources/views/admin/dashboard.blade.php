@extends('layouts.app')

@section('title', __('nav.admin_dashboard'))

@section('content')
@php
    $staffPool = max(1, $summary['active_users'] + $summary['inactive_users']);
    $activeRate = (int) min(100, round(($summary['active_users'] / $staffPool) * 100));

    $iconUsers = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>';
    $iconRoles = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
    $iconAuditToday = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $iconAuditWeek = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
@endphp
<div class="page" x-data="{}">
    @include('partials.page-header', [
        'title' => __('nav.admin_dashboard'),
        'subtitle' => __('admin.dashboard_subtitle'),
    ])

    @include('partials.repayment-summary-strip', [
        'title' => __('admin.dashboard_summary_title'),
        'copy' => __('admin.dashboard_summary_copy', [
            'active' => number_format($summary['active_users']),
            'inactive' => number_format($summary['inactive_users']),
            'roles' => number_format($summary['roles_count']),
        ]),
        'rate' => $activeRate,
        'rateLabel' => __('admin.dashboard_active_rate', ['rate' => $activeRate]),
        'metrics' => [
            [
                'label' => __('admin.dashboard_total_users'),
                'value' => number_format($summary['total_users']),
            ],
            [
                'label' => __('admin.dashboard_active_users'),
                'value' => number_format($summary['active_users']),
                'tone' => 'paid',
            ],
            [
                'label' => __('admin.dashboard_inactive_users'),
                'value' => number_format($summary['inactive_users']),
                'tone' => 'outstanding',
            ],
        ],
    ])

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('partials.dashboard-stat-card', [
            'url' => route('admin.users.index'),
            'gradient' => 'indigo',
            'label' => __('admin.active_users'),
            'value' => number_format($summary['active_users']),
            'meta' => __('admin.dashboard_open_users'),
            'ariaLabel' => __('admin.active_users'),
            'icon' => $iconUsers,
        ])
        @include('partials.dashboard-stat-card', [
            'url' => route('admin.users.inactive'),
            'gradient' => 'cyan',
            'label' => __('admin.deactivated_users'),
            'value' => number_format($summary['inactive_users']),
            'meta' => __('admin.dashboard_open_deactivated_users'),
            'ariaLabel' => __('admin.deactivated_users'),
            'icon' => $iconUsers,
        ])
        @include('partials.dashboard-stat-card', [
            'url' => route('admin.roles.index'),
            'gradient' => 'violet',
            'label' => __('nav.roles'),
            'value' => number_format($summary['roles_count']),
            'meta' => __('admin.dashboard_open_roles'),
            'ariaLabel' => __('nav.roles'),
            'icon' => $iconRoles,
        ])
        @can('view audit logs')
            @include('partials.dashboard-stat-card', [
                'url' => route('admin.audit.index'),
                'gradient' => 'emerald',
                'label' => __('admin.dashboard_audit_today'),
                'value' => number_format($summary['audit_today']),
                'meta' => __('admin.dashboard_open_audit'),
                'ariaLabel' => __('admin.dashboard_audit_today'),
                'icon' => $iconAuditToday,
            ])
            @include('partials.dashboard-stat-card', [
                'url' => route('admin.audit.index'),
                'gradient' => 'cyan',
                'label' => __('admin.dashboard_audit_week'),
                'value' => number_format($summary['audit_week']),
                'meta' => __('admin.dashboard_open_audit'),
                'ariaLabel' => __('admin.dashboard_audit_week'),
                'icon' => $iconAuditWeek,
                'wide' => true,
            ])
        @else
            @include('partials.dashboard-stat-card', [
                'url' => route('admin.dashboard'),
                'gradient' => 'emerald',
                'label' => __('nav.audit_logs'),
                'value' => '—',
                'meta' => __('admin.dashboard_audit_locked'),
                'ariaLabel' => __('nav.audit_logs'),
                'icon' => $iconAuditWeek,
                'constant' => true,
                'wide' => true,
            ])
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('admin.dashboard_users_by_role') }}</h2>
            </div>
            <div class="app-card-padded">
                @if($usersByRole->isEmpty())
                    <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('admin.no_users') }}</p>
                @else
                    <div class="h-64">
                        <canvas id="adminRolesChart" aria-label="{{ __('admin.dashboard_users_by_role') }}"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <div class="app-card overflow-hidden">
            <div class="app-card-header flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">{{ __('admin.dashboard_recent_audit') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">{{ __('admin.dashboard_audit_last_7_days') }}</p>
                </div>
                @can('view audit logs')
                    <a href="{{ route('admin.audit.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('common.view') }}</a>
                @endcan
            </div>
            @can('view audit logs')
                <div class="app-card-padded">
                    <div class="h-64">
                        <canvas id="adminAuditChart" aria-label="{{ __('admin.dashboard_recent_audit') }}"></canvas>
                    </div>
                </div>
            @else
                <p class="app-card-padded text-sm text-slate-500">{{ __('admin.dashboard_audit_locked') }}</p>
            @endcan
        </div>
    </div>
</div>

<script type="application/json" id="admin-dashboard-chart-data">@json($adminChartData)</script>
@vite(['resources/js/pages/admin-dashboard.js'])
@endsection
