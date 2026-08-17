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
