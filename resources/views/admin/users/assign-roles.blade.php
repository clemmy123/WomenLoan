@extends('layouts.app')

@section('title', __('admin.assign_roles'))

@section('content')
<div class="page">
    @include('partials.page-header', [
        'title' => __('admin.assign_roles'),
        'subtitle' => $user->name,
        'actions' => '<a href="'.e(route('admin.users.index')).'" class="app-btn app-btn-secondary">'.e(__('common.back')).'</a>',
    ])

    <form method="POST" action="{{ route('admin.users.assign-roles.update', $user) }}" class="max-w-3xl">
        @csrf
        @method('PUT')

        <div class="app-card app-card-padded">
            <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ __('admin.assign_roles') }}</h3>
            <p class="text-sm text-slate-500 dark:text-zinc-400 mb-4">{{ __('admin.assign_roles_hint') }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-80 overflow-y-auto">
                @foreach($roles as $role)
                    @if($role->name === 'super_admin' && ! auth()->user()->hasRole('super_admin'))
                        @continue
                    @endif
                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-white/5 cursor-pointer">
                        <input
                            type="checkbox"
                            name="roles[]"
                            value="{{ $role->name }}"
                            {{ in_array($role->name, old('roles', $userRoles), true) ? 'checked' : '' }}
                            class="rounded border-slate-300 text-indigo-600"
                        >
                        <span class="text-sm text-slate-800 dark:text-zinc-100">{{ role_label($role->name) }}</span>
                    </label>
                @endforeach
            </div>
            @error('roles') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            @error('roles.*') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        @include('partials.admin-user-geo-zone')

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="app-btn app-btn-primary">{{ __('admin.save_roles') }}</button>
            <a href="{{ route('admin.users.show', $user) }}" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
