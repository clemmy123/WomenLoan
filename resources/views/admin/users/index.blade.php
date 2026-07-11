@extends('layouts.app')

@section('title', __('nav.users'))

@section('content')
<div class="page">
    @include('partials.page-header', [
        'title' => __('nav.users'),
        'subtitle' => __('admin.users_subtitle'),
        'actions' => '<a href="'.e(route('admin.users.create')).'" class="app-btn app-btn-primary">+ '.e(__('admin.new_user')).'</a>',
    ])

    <div class="app-card app-card-padded mb-4">
        @include('partials.loan-list-toolbar', [
            'action' => route('admin.users.index'),
            'search' => $search,
            'sort' => $role,
            'sortName' => 'role',
            'sortLabel' => __('admin.sort_by_role'),
            'status' => $status,
            'searchPlaceholder' => __('admin.users_search_placeholder'),
            'sortOptions' => $roleOptions,
            'statusOptions' => [
                '' => __('admin.status_all'),
                'active' => __('common.active'),
                'inactive' => __('common.inactive'),
            ],
            'showClear' => filled($search) || filled($status) || filled($role),
            'clearUrl' => route('admin.users.index'),
        ])
    </div>

<div class="app-card overflow-hidden">
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
                    @foreach($user->roles as $role)
                        @include('partials.badge', ['variant' => 'primary', 'text' => role_label($role->name), 'class' => 'mr-1 mb-1'])
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
                        @include('partials.table-icon', ['action' => 'edit', 'href' => route('admin.users.edit', $user), 'label' => __('common.edit')])
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="app-table-empty">{{ __('admin.no_users') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="app-card-footer">{{ $users->links() }}</div>
</div>
</div>
@endsection
