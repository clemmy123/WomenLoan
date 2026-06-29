@extends('layouts.app')

@section('title', __('admin.edit_role'))

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('admin.edit_role') }}: {{ role_label($role->name) }}</h1>
        <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">{{ __('admin.edit_role_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.roles.index') }}" class="text-sm font-semibold text-slate-600 dark:text-zinc-400 hover:underline">← {{ __('admin.back_to_roles') }}</a>
</div>

@if($role->hasLockedPermissions())
<div class="mb-6 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
    {{ __('admin.all_permissions_locked') }}
</div>
@endif

<form method="POST" action="{{ route('admin.roles.update', $role) }}">
    @csrf @method('PUT')

    @if(!$role->isProtected())
    <div class="bg-white dark:dark-surface rounded-2xl border border-slate-200 dark:border-white/[0.08] p-6 mb-6">
        <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('admin.role_details') }}</h3>
        <div class="max-w-md">
            <label class="app-label" for="role_name">{{ __('admin.role_key') }}</label>
            <input type="text" name="name" id="role_name" value="{{ old('name', $role->name) }}" required
                pattern="[a-z][a-z0-9_]*"
                class="app-input font-mono">
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
    @endif

    <div class="bg-white dark:dark-surface rounded-2xl border border-slate-200 dark:border-white/[0.08] p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white">{{ __('admin.permissions') }}</h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('admin.permissions_menu_hint') }}</p>
            </div>
            @if(!$role->hasLockedPermissions())
            <label class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 cursor-pointer">
                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-indigo-600"
                    onchange="document.querySelectorAll('.perm-check').forEach(c => c.checked = this.checked)">
                {{ __('admin.select_all') }}
            </label>
            @endif
        </div>

        @if($role->hasLockedPermissions())
        <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('admin.super_admin_perms_list', ['count' => $role->permissions->count()]) }}</p>
        @else
        @include('admin.roles._permission_grid', [
            'permissionGroups' => $permissionGroups,
            'menuHints' => $menuHints,
            'rolePermissions' => $rolePermissions,
        ])
        @endif
    </div>

    @if(!$role->hasLockedPermissions())
    <div class="mt-6 flex gap-3">
        <button type="submit" data-loading-text="{{ __('common.saving') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm">{{ __('admin.save_permissions') }}</button>
        <a href="{{ route('admin.roles.index') }}" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-white/5">{{ __('common.cancel') }}</a>
    </div>
    @endif
</form>

@if($role->isDeletable())
<form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="mt-8"
    onsubmit="return confirm(@json(__('admin.delete_role_confirm')));">
    @csrf @method('DELETE')
    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700 dark:text-red-400">{{ __('admin.delete_role') }}</button>
</form>
@elseif(!$role->isProtected())
<p class="mt-8 text-xs text-slate-400 dark:text-zinc-500">{{ __('admin.cannot_delete_role_in_use') }}</p>
@endif
@endsection
