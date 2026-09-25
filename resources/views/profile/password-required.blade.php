@extends('layouts.app')

@section('title', __('auth.set_own_password'))

@section('content')
<div class="app-page app-page-narrow">
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">{{ __('auth.set_own_password') }}</h1>
        </div>
    </div>

    @unless(session('warning'))
        @include('partials.status-card', [
            'type' => 'error',
            'message' => __('auth.temporary_password_must_change', ['minutes' => $minutes]),
            'class' => 'mb-4',
        ])
    @endunless

    <div class="app-card app-card-padded max-w-lg">
        @if ($expiresAt)
            <p class="mb-4 text-sm font-medium text-red-600 dark:text-red-400">
                {{ __('auth.temporary_password_deadline', ['time' => format_app_datetime($expiresAt, withSeconds: true)]) }}
            </p>
        @endif

        <form method="POST" action="{{ route('profile.password.required.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="app-label" for="current_password">{{ __('profile.current_password') }} @include('partials.required-mark')</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="current-password" class="app-input">
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="app-label" for="password">{{ __('profile.new_password') }} @include('partials.required-mark')</label>
                <input type="password" name="password" id="password" required autocomplete="new-password" class="app-input">
                @include('partials.password-requirements', ['targetId' => 'password', 'variant' => 'app'])
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="app-label" for="password_confirmation">{{ __('common.confirm_password') }} @include('partials.required-mark')</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" class="app-input">
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="app-btn app-btn-primary">{{ __('auth.save_own_password') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
