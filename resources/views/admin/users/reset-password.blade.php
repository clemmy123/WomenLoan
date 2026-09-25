@extends('layouts.app')

@section('title', __('admin.reset_password_emergency'))

@section('content')
<div class="app-page">
    @include('partials.page-header', [
        'title' => __('admin.reset_password_emergency'),
        'subtitle' => $user->name,
        'actions' => '<a href="'.e(route('admin.users.show', $user)).'" class="app-btn app-btn-secondary">'.e(__('common.back')).'</a>',
    ])

    <form method="POST" action="{{ route('admin.users.reset-password.update', $user) }}" class="max-w-xl space-y-4">
        @csrf

        <div class="app-card app-card-padded space-y-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 space-y-1">
                <p class="text-sm font-semibold text-amber-900">{{ __('admin.reset_password_emergency_title') }}</p>
                <p class="text-sm text-amber-900">{{ __('admin.reset_password_emergency_hint') }}</p>
            </div>

            <div>
                <label class="app-label" for="admin_password">{{ __('common.password') }} @include('partials.required-mark')</label>
                <input type="password" name="password" id="admin_password" required class="app-input" autocomplete="new-password">
                @include('partials.password-requirements', ['targetId' => 'admin_password', 'variant' => 'app'])
                <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.temporary_password_hint', ['minutes' => (int) config('wdf.temporary_password_minutes', 2)]) }}</p>
                @error('password') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="app-label" for="password_confirmation">{{ __('common.confirm_password') }} @include('partials.required-mark')</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required class="app-input" autocomplete="new-password">
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="app-btn app-btn-primary">{{ __('admin.reset_password_emergency_confirm') }}</button>
            <a href="{{ route('admin.users.show', $user) }}" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
