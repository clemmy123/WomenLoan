@extends('layouts.app')

@section('title', __('nav.change_password'))

@section('content')
<div class="page page-narrow">
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('nav.change_password') }}</h1>
            <p class="page-subtitle">{{ __('profile.change_password_hint') }}</p>
        </div>
    </div>

    <div class="app-card app-card-padded max-w-lg">
        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="app-label" for="current_password">{{ __('profile.current_password') }} @include('partials.required-mark')</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="current-password" class="app-input">
            </div>

            <div>
                <label class="app-label" for="password">{{ __('profile.new_password') }} @include('partials.required-mark')</label>
                <input type="password" name="password" id="password" required autocomplete="new-password" class="app-input">
            </div>

            <div>
                <label class="app-label" for="password_confirmation">{{ __('common.confirm_password') }} @include('partials.required-mark')</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" class="app-input">
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="app-btn app-btn-primary">{{ __('profile.update_password') }}</button>
                <a href="{{ route('dashboard') }}" class="app-btn app-btn-secondary">{{ __('common.back') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
