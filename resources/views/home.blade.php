<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>{{ __('home.portal_name') }} | {{ __('nav.welcome') }}</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    @include('partials.accessibility-head-script')
    @vite(['resources/css/app.css', 'resources/js/pages/landing.js'])
</head>
<body class="landing-page min-h-screen flex flex-col" x-data="landingHeader()" x-init="init()" @scroll.window.passive="onScroll()">
    <div
        class="landing-header-spacer"
        x-show="floating"
        x-cloak
        :style="floating ? `height: ${headerHeight}px` : null"
        aria-hidden="true"
    ></div>
    <div class="landing-header" x-ref="header" :class="{ 'is-floating': floating }">
        <header class="landing-nav">
            <a href="{{ route('home') }}" class="landing-brand">
                <span class="landing-brand-logo-wrap">
                    <img src="{{ asset('images/nembo2.png') }}" alt="" class="landing-brand-logo" decoding="async">
                </span>
                <span class="landing-brand-name">{{ __('home.portal_name') }}</span>
            </a>

            <nav class="landing-nav-links" aria-label="Main">
                <a href="#help" class="landing-nav-link">{{ __('home.help') }}</a>
                <a href="#guide" class="landing-nav-link">{{ __('home.user_guide') }}</a>
            </nav>

            <div class="landing-nav-actions">
                @include('partials.accessibility-panel', ['variant' => 'landing'])
                <div class="landing-nav-locale" aria-label="{{ __('nav.language') }}">
                    @include('partials.locale-flags')
                </div>
                <a href="{{ route('login') }}" class="app-btn app-btn-landing-signin">
                    <span>{{ __('home.sign_in') }}</span>
                    <svg class="landing-signin-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 4h4v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 14L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18 6h-5M18 6v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 8v10a2 2 0 002 2h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </a>
            </div>
        </header>
    </div>

    <main class="landing-main">
        <section class="landing-hero">
            <h1 class="landing-headline">{{ __('home.headline') }}</h1>
            <p class="landing-subheadline">{{ __('home.subheadline') }}</p>
        </section>

        @php
            $landingStats = $landingStats ?? [];
        @endphp

        @if(count($landingStats) > 0)
            <section class="landing-stats" aria-label="{{ __('home.stats_section_label') }}" data-landing-stats>
                <div class="landing-stats-bar">
                    @foreach($landingStats as $stat)
                        <div @class([
                            'landing-stat-item',
                            'landing-stat-item--accent' => $stat['key'] === 'applications',
                        ])>
                            <p
                                class="landing-stat-value"
                                data-counter
                                data-target="{{ (int) $stat['value'] }}"
                                aria-label="{{ number_format($stat['value']) }}"
                            >0</p>
                            <p class="landing-stat-label">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="landing-prompt-card">
            <p class="landing-prompt-text">{{ __('home.prompt_text') }}</p>
            <a href="{{ route('register') }}" class="app-btn app-btn-landing-register">
                <span>{{ __('home.register_here') }}</span>
                <svg class="landing-signin-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M14 4h4v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 14L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18 6h-5M18 6v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 8v10a2 2 0 002 2h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </a>
        </div>

        <div class="landing-info-grid">
        <section id="help" class="landing-info-section">
            <div class="landing-info-card">
                <h2 class="landing-info-title">{{ __('home.help_title') }}</h2>
                <p class="landing-info-text">{{ __('home.help_text') }}</p>
            </div>
        </section>

        <section id="guide" class="landing-info-section">
            <div class="landing-info-card">
                <h2 class="landing-info-title">{{ __('home.guide_title') }}</h2>
                <p class="landing-info-text">{{ __('home.guide_text') }}</p>
            </div>
        </section>
        </div>
    </main>

    <footer class="landing-footer">
        <p class="landing-footer-text">{{ __('home.footer_copyright') }}</p>
    </footer>
</body>
</html>
