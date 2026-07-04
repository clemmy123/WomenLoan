@extends('layouts.auth-form')

@section('auth_title', __('auth.forgot_password'))

@section('content')
<div class="auth-split-form-wrap auth-form-wrap">
    <div class="auth-split-form-header">
        <h2 class="auth-split-form-title">{{ __('auth.forgot_password') }}</h2>
        <p class="auth-split-form-subtitle">{{ __('auth.forgot_password_subtitle') }}</p>
    </div>

    @if (session('status'))
        <div class="auth-split-alert auth-split-alert--success" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="auth-split-alert" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-split-form">
        @csrf

        <div class="auth-split-field">
            <label class="auth-split-label" for="email">{{ __('common.email') }}</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus class="auth-split-input" placeholder="you@example.com">
            </div>
        </div>

        <button type="submit" data-loading-text="{{ __('common.loading') }}" class="auth-split-submit">
            <span>{{ __('auth.send_reset_link') }}</span>
            <svg class="auth-split-submit-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75"/>
                <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>

    <div class="auth-split-footer-link">
        <span>{{ __('auth.login_prompt') }}</span>
        <a href="{{ route('login') }}">{{ __('home.sign_in') }}</a>
    </div>
</div>
@endsection
