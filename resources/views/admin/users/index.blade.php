@extends('layouts.app')

@section('title', __('nav.users'))

@section('content')
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ __('nav.users') }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ __('admin.users_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl">+ {{ __('admin.new_user') }}</a>
</div>

<div class="app-card overflow-hidden">
    <table class="app-table">
        <thead>
            <tr>
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
            <tr><td colspan="5" class="app-table-empty">{{ __('admin.no_users') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="app-card-footer">{{ $users->links() }}</div>
</div>
@endsection
