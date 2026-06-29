@extends('layouts.app')

@section('title', __('nav.roles'))

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('nav.roles') }}</h1>
        <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">{{ __('admin.roles_index_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl">
        + {{ __('admin.create_role') }}
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($roles as $role)
    <div class="bg-white dark:dark-surface rounded-2xl border border-slate-200 dark:border-white/[0.08] p-5 transition">
        <div class="flex justify-between items-start mb-3">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white">{{ role_label($role->name) }}</h3>
                <p class="text-[10px] font-mono text-slate-400 dark:text-zinc-500 mt-0.5">{{ $role->name }}</p>
            </div>
            @include('partials.badge', ['variant' => 'secondary', 'text' => __('admin.perms_count', ['count' => $role->permissions->count()])])
        </div>
        <p class="text-xs text-slate-500 dark:text-zinc-400 mb-2">
            {{ __('admin.users_with_role', ['count' => $role->users_count]) }}
        </p>
        <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4 line-clamp-2">
            {{ $role->permissions->take(3)->map(fn ($p) => permission_label($p->name))->join(', ') }}{{ $role->permissions->count() > 3 ? '...' : '' }}
        </p>
        <div class="flex items-center gap-3">
            @if(!$role->hasLockedPermissions())
                <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 dark:text-indigo-400 text-xs font-semibold hover:underline">{{ __('admin.edit_permissions') }} →</a>
            @else
                <span class="text-xs text-slate-400 dark:text-zinc-500">{{ __('admin.all_permissions_locked') }}</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection
