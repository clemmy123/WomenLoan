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

        <section
            class="landing-carousel-wrap"
            x-data="landingCarousel({{ count($landingStats) ?: 5 }})"
            @mouseenter="stopAutoplay()"
            @mouseleave="startAutoplay()"
        >
            <div class="landing-carousel">
                @forelse($landingStats as $index => $stat)
                    <article
                        class="landing-carousel-slide landing-carousel-slide--{{ $stat['theme'] }}"
                        :class="slideClass({{ $index }})"
                        :aria-hidden="active !== {{ $index }}"
                        aria-label="{{ $stat['label'] }}: {{ number_format($stat['value']) }}"
                    >
                        <div class="landing-slide-glow"></div>
                        <div class="landing-slide-content landing-slide-content--stat">
                            <h3 class="landing-slide-title landing-stat-title">
                                {{ $stat['label'] }}:
                                <span class="landing-stat-value">{{ number_format($stat['value']) }}</span>
                            </h3>
                            <p class="landing-slide-caption">{{ $stat['caption'] }}</p>
                        </div>
                    </article>
                @empty
                    @php
                        $slides = __('home.slides');
                        $slideThemes = ['violet', 'cyan', 'indigo', 'cyan', 'violet'];
                    @endphp
                    @foreach($slides as $index => $slide)
                        <article
                            class="landing-carousel-slide landing-carousel-slide--{{ $slideThemes[$index] ?? 'indigo' }}"
                            :class="slideClass({{ $index }})"
                            :aria-hidden="active !== {{ $index }}"
                        >
                            <div class="landing-slide-glow"></div>
                            <div class="landing-slide-content">
                                <span class="landing-slide-badge">{{ $index + 1 }}</span>
                                <h3 class="landing-slide-title">{{ $slide['title'] }}</h3>
                                <p class="landing-slide-caption">{{ $slide['caption'] }}</p>
                            </div>
                        </article>
                    @endforeach
                @endforelse
            </div>

            <div class="landing-carousel-controls">
                <button type="button" class="landing-carousel-btn" @click="prev()" aria-label="{{ __('common.back') }}">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="landing-carousel-btn" @click="next()" aria-label="{{ __('common.next') }}">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </section>

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
