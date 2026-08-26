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
    <style>
        /* Critical auth toolbar — works when Vite/build assets are missing */
        .jj-auth-card-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.85rem}
        .jj-auth-toolbar-actions{display:inline-flex;align-items:center;gap:.45rem;flex-shrink:0}
        .jj-auth-back{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .75rem .35rem .5rem;border-radius:999px;color:#1a4a8a;font-size:.78rem;font-weight:700;line-height:1.2;text-decoration:none;background:linear-gradient(115deg,#dbeafe 0%,#e8f1ff 45%,#f0f6ff 100%);border:1px solid rgba(147,197,253,.75);white-space:nowrap;transition:transform .2s ease,background .2s ease,color .2s ease,box-shadow .2s ease}
        .jj-auth-back svg{width:.95rem;height:.95rem;flex-shrink:0;display:block}
        .jj-auth-back:hover{color:#fff;background:linear-gradient(115deg,#095456 0%,#0d7377 40%,#14a3a8 100%);transform:translateY(-1px);box-shadow:0 10px 22px rgba(13,115,119,.28)}
    </style>
    @stack('head')
</head>
<body class="jj-auth-page">
    <div class="jj-auth-frame">
        <div class="jj-auth-shell jj-auth-shell--form-only">
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
