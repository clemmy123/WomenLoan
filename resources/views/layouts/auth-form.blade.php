<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full" data-loading-text="{{ __('common.loading') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | @yield('auth_title', __('nav.register'))</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    @include('partials.accessibility-head-script')
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="auth-split-page auth-form-page">
    <div class="auth-split-ambient" aria-hidden="true">
        <span class="auth-split-orb auth-split-orb--violet"></span>
        <span class="auth-split-orb auth-split-orb--cyan"></span>
        <span class="auth-split-orb auth-split-orb--rose"></span>
    </div>

    <div class="auth-form-shell">
        <div class="auth-form-card">
            <div class="auth-form-card-inner">
                <div class="auth-split-main-toolbar auth-form-toolbar">
                    <a href="{{ route('home') }}" class="auth-split-back-link">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ __('auth.back_home') }}</span>
                    </a>
                    <div class="auth-split-toolbar-actions">
                        @include('partials.accessibility-panel', ['variant' => 'auth'])
                        @include('partials.locale-flags')
                    </div>
                </div>

                @yield('content')
            </div>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
