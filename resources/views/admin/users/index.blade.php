@extends('layouts.app')

@section('title', $listStatus === 'inactive' ? __('admin.deactivated_users') : __('nav.users'))

@section('content')
@php
    $isInactiveList = $listStatus === 'inactive';
    $listRoute = $isInactiveList ? route('admin.users.inactive') : route('admin.users.index');
    $exportQuery = array_filter([
        'search' => $search ?: null,
        'role' => $role ?: null,
        'list' => $listStatus,
    ]);
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

    <div class="app-card app-card-padded mb-4">
        @include('partials.loan-list-toolbar', [
            'action' => $listRoute,
            'search' => $search,
            'sort' => $role,
            'sortName' => 'role',
            'sortLabel' => __('admin.sort_by_role'),
            'searchPlaceholder' => __('admin.users_search_placeholder'),
            'sortOptions' => $roleOptions,
            'showClear' => filled($search) || filled($role),
            'clearUrl' => $listRoute,
        ])
    </div>

<div class="app-card">
    <div class="overflow-x-auto">
    <table class="app-table">
        <thead>
            <tr>
                <th>{{ __('admin.check_number') }}</th>
                <th>{{ __('common.name') }}</th>
                <th>{{ __('common.email') }}</th>
                <th>{{ __('common.roles') }}</th>
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
                <td colspan="6" class="app-table-empty">
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
