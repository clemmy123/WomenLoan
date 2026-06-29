@extends('layouts.app')

@section('title', __('admin.create_role'))

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('admin.create_role') }}</h1>
        <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">{{ __('admin.create_role_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.roles.index') }}" class="text-sm font-semibold text-slate-600 dark:text-zinc-400 hover:underline">← {{ __('admin.back_to_roles') }}</a>
</div>

<form method="POST" action="{{ route('admin.roles.store') }}">
    @csrf

    <div class="bg-white dark:dark-surface rounded-2xl border border-slate-200 dark:border-white/[0.08] p-6 mb-6">
        <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('admin.role_details') }}</h3>
        <div class="max-w-md">
            <label class="app-label" for="name">{{ __('admin.role_key') }}</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                pattern="[a-z][a-z0-9_]*"
                placeholder="e.g. field_officer"
                class="app-input font-mono">
            <p class="text-xs text-slate-400 dark:text-zinc-500 mt-1">{{ __('admin.role_key_hint') }}</p>
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="bg-white dark:dark-surface rounded-2xl border border-slate-200 dark:border-white/[0.08] p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white">{{ __('admin.permissions') }}</h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('admin.permissions_menu_hint') }}</p>
            </div>
            <label class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 cursor-pointer">
                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-indigo-600"
                    onchange="document.querySelectorAll('.perm-check').forEach(c => c.checked = this.checked)">
                {{ __('admin.select_all') }}
            </label>
        </div>

        @include('admin.roles._permission_grid', [
            'permissionGroups' => $permissionGroups,
            'menuHints' => $menuHints,
            'rolePermissions' => [],
        ])
    </div>

    <div class="mt-6 flex gap-3">
        <button type="submit" data-loading-text="{{ __('common.saving') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm">{{ __('admin.create_role') }}</button>
        <a href="{{ route('admin.roles.index') }}" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-white/5">{{ __('common.cancel') }}</a>
    </div>
</form>
@endsection
