@extends('layouts.app')

@section('title', __('admin.assign_roles'))

@section('content')
<div class="page">
    @include('partials.page-header', [
        'title' => __('admin.assign_roles'),
        'subtitle' => $user->name,
        'actions' => '<a href="'.e(route('admin.users.index')).'" class="app-btn app-btn-secondary">'.e(__('common.back')).'</a>',
    ])

    <form
        method="POST"
        action="{{ route('admin.users.assign-roles.update', $user) }}"
        class="max-w-3xl"
        data-staff-user-form
        data-role-zone-map='@json(\App\Support\StaffZone::GEO_ROLE_PRIORITY)'
        data-roles-required="{{ __('admin.roles_required') }}"
        data-geo-zone-required="{{ __('admin.geo_zone_required') }}"
        data-geo-zone-incomplete="{{ __('admin.geo_zone_incomplete') }}"
        data-complete-section-here="{{ __('admin.complete_section_here') }}"
        data-ok-label="{{ __('common.ok') }}"
        data-error-label="{{ __('common.error') }}"
        novalidate
    >
        @csrf
        @method('PUT')

        @php
            $selectedRole = old('role', old('roles.0', $userRoles[0] ?? ''));
            $userRoles = $userRoles ?? [];
        @endphp

        <div
            id="staff-role-section"
            class="app-card app-card-padded {{ $errors->has('role') || $errors->has('roles') ? 'ring-2 ring-red-400' : '' }}"
            data-staff-role-section
            @if($errors->has('role') || $errors->has('roles')) data-error-anchor @endif
        >
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('admin.assign_roles') }} @include('partials.required-mark')</h3>
            <p class="text-sm text-slate-500 dark:text-zinc-400 mb-4">{{ __('admin.roles_required') }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-80 overflow-y-auto @error('role') ring-2 ring-red-300 rounded-lg p-1 @enderror">
                @foreach($roles as $role)
                    @if($role->name === 'super_admin' && ! auth()->user()->hasRole('super_admin'))
                        @continue
                    @endif
                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-white/5 cursor-pointer">
                        <input
                            type="radio"
                            name="role"
                            value="{{ $role->name }}"
                            {{ (string) $selectedRole === (string) $role->name ? 'checked' : '' }}
                            required
                            class="border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="text-sm text-slate-800 dark:text-zinc-100">{{ role_label($role->name) }}</span>
                    </label>
                @endforeach
            </div>
            <p class="mt-2 text-xs font-medium text-red-600" data-staff-gap-message hidden></p>
            @error('role') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            @error('roles') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        @include('partials.admin-user-geo-zone')

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="app-btn app-btn-primary">{{ __('admin.save_roles') }}</button>
            <a href="{{ route('admin.users.show', $user) }}" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
