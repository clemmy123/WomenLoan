@extends('layouts.auth-form')

@section('auth_title', __('nav.register'))

@section('content')
<div class="auth-split-form-wrap auth-form-wrap">
    <div class="auth-split-form-header">
        <h2 class="auth-split-form-title">{{ __('auth.register_title') }}</h2>
        <p class="auth-split-form-subtitle">{{ __('auth.register_subtitle') }}</p>
    </div>

    @include('partials.auth-flash-messages')

    <form method="POST" action="{{ route('register') }}" class="auth-split-form">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="auth-split-field">
                <label class="auth-split-label" for="first_name">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
                <div class="auth-split-input-wrap">
                    <span class="auth-split-input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.75"/><path d="M5 19c0-3.3 3.1-5 7-5s7 1.7 7 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                    </span>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required autofocus class="auth-split-input" placeholder="{{ __('applicants.first_name') }}">
                </div>
            </div>
            <div class="auth-split-field">
                <label class="auth-split-label" for="middle_name">{{ __('applicants.middle_name') }}</label>
                <div class="auth-split-input-wrap">
                    <span class="auth-split-input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.75"/><path d="M5 19c0-3.3 3.1-5 7-5s7 1.7 7 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                    </span>
                    <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}" class="auth-split-input" placeholder="{{ __('applicants.middle_name') }}">
                </div>
            </div>
            <div class="auth-split-field">
                <label class="auth-split-label" for="last_name">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
                <div class="auth-split-input-wrap">
                    <span class="auth-split-input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.75"/><path d="M5 19c0-3.3 3.1-5 7-5s7 1.7 7 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                    </span>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required class="auth-split-input" placeholder="{{ __('applicants.last_name') }}">
                </div>
            </div>
        </div>

        <div class="auth-split-field">
            <label class="auth-split-label" for="email">{{ __('common.email') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="auth-split-input" placeholder="you@example.com">
            </div>
        </div>

        <div class="auth-split-field">
            <label class="auth-split-label" for="phone_local">{{ __('auth.phone') }} @include('partials.required-mark')</label>
            @include('partials.inputs.phone-input', [
                'name' => 'phone',
                'id' => 'phone_local',
                'value' => old('phone'),
                'required' => true,
                'class' => 'auth-form-phone-local',
            ])
        </div>

        <div class="auth-split-field" x-data="{ showPassword: false }">
            <label class="auth-split-label" for="password">{{ __('common.password') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                </span>
                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required class="auth-split-input auth-split-input--password" placeholder="••••••••">
                <button type="button" @click="showPassword = !showPassword" class="auth-split-password-toggle"
                    :aria-label="showPassword ? @json(__('auth.hide_password')) : @json(__('auth.show_password'))">
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @include('partials.password-requirements', ['targetId' => 'password', 'variant' => 'auth'])
        </div>

        <div class="auth-split-field" x-data="{ showPassword: false }">
            <label class="auth-split-label" for="password_confirmation">{{ __('common.confirm_password') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                </span>
                <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required class="auth-split-input auth-split-input--password" placeholder="••••••••">
                <button type="button" @click="showPassword = !showPassword" class="auth-split-password-toggle"
                    :aria-label="showPassword ? @json(__('auth.hide_password')) : @json(__('auth.show_password'))">
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="auth-split-submit">
            <span>{{ __('nav.register') }}</span>
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
