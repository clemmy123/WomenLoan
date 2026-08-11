<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full" data-loading-text="{{ __('common.loading') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | @yield('auth_title', __('nav.register'))</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    @include('partials.accessibility-head-script')
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
@php
    $locale = app()->getLocale();
    $isSw = $locale === 'sw';
    $slides = [
        [
            'tag' => $isSw ? 'LANGO MOJA' : 'ONE GATEWAY',
            'title' => $isSw
                ? 'Fungua akaunti ya huduma ya jamii'
                : 'Create your community service account',
            'body' => $isSw
                ? 'Usajili ni kwa huduma unayoichagua — anza na WDF na endelea kwa urahisi.'
                : 'Registration is per service you choose — start with WDF and continue with ease.',
        ],
        [
            'tag' => $isSw ? 'KAULI MBIU' : 'OUR MOTTO',
            'title' => $isSw
                ? 'Familia Imara | Jamii Imara | Taifa Imara'
                : 'Strong Family | Strong Community | Strong Nation',
            'body' => $isSw
                ? 'Jamii Jumuishi inaunganisha huduma za maendeleo, ustawi, na uwezeshaji.'
                : 'Jamii Jumuishi connects development, welfare, and empowerment services.',
        ],
        [
            'tag' => $isSw ? 'USALAMA' : 'SECURITY',
            'title' => $isSw ? 'Usajili salama na rahisi' : 'Simple and secure registration',
            'body' => $isSw
                ? 'Thibitisha utambulisho wako na unda akaunti kwa usalama.'
                : 'Verify your identity and create your account securely.',
        ],
    ];
@endphp
<body
    class="jj-auth-page"
    x-data="{
        slide: 0,
        slides: @js($slides),
        init() {
            setInterval(() => { this.slide = (this.slide + 1) % this.slides.length }, 4500)
        }
    }"
>
    <div class="jj-auth-frame">
        <div class="jj-auth-shell">
            <aside class="jj-auth-left" aria-label="Jamii Jumuishi">
                <div class="jj-auth-logo-pane">
                    <div class="jj-auth-logo-wrap">
                        <img
                            src="{{ asset('brand/jamii-ajenda-2050.png') }}"
                            alt="Jamii Ajenda 2050"
                            class="jj-auth-logo"
                            decoding="async"
                        >
                    </div>
                    <p class="jj-auth-motto">
                        @if ($isSw)
                            <span>Familia Imara</span>
                            <span class="jj-auth-motto-sep" aria-hidden="true">|</span>
                            <span>Jamii Imara</span>
                            <span class="jj-auth-motto-sep" aria-hidden="true">|</span>
                            <span>Taifa Imara</span>
                        @else
                            <span>Strong Family</span>
                            <span class="jj-auth-motto-sep" aria-hidden="true">|</span>
                            <span>Strong Community</span>
                            <span class="jj-auth-motto-sep" aria-hidden="true">|</span>
                            <span>Strong Nation</span>
                        @endif
                    </p>
                </div>

                <div class="jj-auth-scroll" aria-live="polite">
                    <p class="jj-auth-scroll-tag">
                        <span class="jj-auth-scroll-dot" aria-hidden="true"></span>
                        <span x-text="slides[slide].tag"></span>
                    </p>
                    <h2 class="jj-auth-scroll-title" x-text="slides[slide].title"></h2>
                    <p class="jj-auth-scroll-body" x-text="slides[slide].body"></p>

                    <div class="jj-auth-scroll-dots" role="tablist" aria-label="Highlights">
                        <template x-for="(s, i) in slides" :key="i">
                            <button
                                type="button"
                                class="jj-auth-scroll-pill"
                                :class="{ 'is-active': i === slide }"
                                :aria-label="s.tag"
                                :aria-selected="i === slide"
                                @click="slide = i"
                            ></button>
                        </template>
                    </div>
                </div>
            </aside>

            <main class="jj-auth-right">
                <div class="jj-auth-card-toolbar">
                    <a href="{{ route('home') }}" class="jj-auth-back">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>{{ __('auth.back_home') }}</span>
                    </a>
                    <div class="jj-auth-toolbar-actions">
                        @include('partials.accessibility-panel', ['variant' => 'auth'])
                        @include('partials.locale-flags')
                    </div>
                </div>

                @yield('content')

                <p class="jj-auth-copy">© {{ date('Y') }} Jamii Jumuishi. All rights reserved.</p>
            </main>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
