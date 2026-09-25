@extends('layouts.auth-form')

@section('auth_title', __('auth.reset_password'))

@section('content')
<div class="auth-split-form-wrap auth-form-wrap">
    <div class="auth-split-form-header">
        <h2 class="auth-split-form-title">{{ __('auth.reset_password') }}</h2>
        <p class="auth-split-form-subtitle">{{ __('auth.reset_password_subtitle') }}</p>
    </div>

    @include('partials.auth-flash-messages')

    <form method="POST" action="{{ route('password.update') }}" class="auth-split-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="auth-split-field">
            <label class="auth-split-label" for="email">{{ __('common.email') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required autofocus class="auth-split-input" placeholder="you@example.com">
            </div>
        </div>

        <div class="auth-split-field">
            <label class="auth-split-label" for="password">{{ __('common.password') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                </span>
                <input type="password" name="password" id="password" required class="auth-split-input auth-split-input--password" placeholder="••••••••">
                @include('partials.password-toggle')
            </div>
            @include('partials.password-requirements', ['targetId' => 'password', 'variant' => 'auth'])
        </div>

        <div class="auth-split-field">
            <label class="auth-split-label" for="password_confirmation">{{ __('common.confirm_password') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                </span>
                <input type="password" name="password_confirmation" id="password_confirmation" required class="auth-split-input auth-split-input--password" placeholder="••••••••">
                @include('partials.password-toggle')
            </div>
        </div>

        <button type="submit" data-loading-text="{{ __('common.loading') }}" class="auth-split-submit">
            <span>{{ __('auth.reset_password') }}</span>
            <svg class="auth-split-submit-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M14 4h4v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 14 18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M18 6h-5M18 6v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 8v10a2 2 0 002 2h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </form>

    <div class="auth-split-footer-link">
        <span>{{ __('auth.login_prompt') }}</span>
        <a href="{{ route('login') }}">{{ __('home.sign_in') }}</a>
    </div>
</div>
@endsection
