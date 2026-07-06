<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full" data-loading-text="{{ __('common.loading') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | @yield('auth_title', __('nav.login'))</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    @include('partials.accessibility-head-script')
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="auth-split-page">
    <div class="auth-split-ambient" aria-hidden="true">
        <span class="auth-split-orb auth-split-orb--violet"></span>
        <span class="auth-split-orb auth-split-orb--cyan"></span>
        <span class="auth-split-orb auth-split-orb--rose"></span>
    </div>

    <div class="auth-split-card">
        <aside class="auth-split-brand" aria-label="{{ __('nav.welcome') }}">
            <div class="auth-split-brand-mesh" aria-hidden="true"></div>
            <div class="auth-split-brand-glow" aria-hidden="true"></div>

            <div class="auth-split-brand-content auth-split-brand-content--minimal">
                <a href="{{ route('home') }}" class="auth-split-brand-link">
                    <span class="auth-split-logo-wrap">
                        <img src="{{ asset('images/nembo2.png') }}" alt="{{ __('nav.welcome') }}" class="auth-split-logo" decoding="async">
                    </span>
                    <span class="auth-split-portal-name">{{ __('home.portal_name') }}</span>
                </a>

                @include('partials.auth-login-features')
            </div>

            <p class="auth-split-brand-footnote">{{ __('nav.platform') }}</p>

            <div class="auth-split-waves" aria-hidden="true">
                <svg viewBox="0 0 1440 160" preserveAspectRatio="none" class="auth-split-wave auth-split-wave--back">
                    <path d="M0,96L48,101.3C96,107,192,117,288,112C384,107,480,85,576,80C672,75,768,85,864,90.7C960,96,1056,96,1152,90.7C1248,85,1344,75,1392,69.3L1440,64L1440,160L0,160Z"/>
                </svg>
                <svg viewBox="0 0 1440 160" preserveAspectRatio="none" class="auth-split-wave auth-split-wave--front">
                    <path d="M0,112L48,106.7C96,101,192,91,288,93.3C384,96,480,117,576,122.7C672,128,768,117,864,106.7C960,96,1056,85,1152,85.3C1248,85,1344,96,1392,101.3L1440,107L1440,160L0,160Z"/>
                </svg>
            </div>
        </aside>

        <main class="auth-split-main">
            <div class="auth-split-main-toolbar">
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
        </main>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
