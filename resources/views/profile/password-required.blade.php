@extends('layouts.app')

@section('title', __('auth.set_own_password'))

@section('content')
<div class="page page-narrow">
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('auth.set_own_password') }}</h1>
            <p class="page-subtitle">{{ __('auth.temporary_password_must_change', ['minutes' => $minutes]) }}</p>
        </div>
    </div>

    <div class="app-card app-card-padded max-w-lg">
        @if ($expiresAt)
            <p class="mb-4 text-sm text-amber-700 dark:text-amber-300">
                {{ __('auth.temporary_password_deadline', ['time' => format_app_datetime($expiresAt, withSeconds: true)]) }}
            </p>
        @endif

        <form method="POST" action="{{ route('profile.password.required.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

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
