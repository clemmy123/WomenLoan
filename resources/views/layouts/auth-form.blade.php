<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full" data-loading-text="{{ __('common.loading') }}"
    x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
    x-init="$watch('dark', v => { localStorage.setItem('theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v) }); document.documentElement.classList.toggle('dark', dark)"
    :class="dark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | @yield('auth_title', __('nav.register'))</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    <script>
        (function(){var d=localStorage.getItem('theme')==='dark';if(d)document.documentElement.classList.add('dark')})();
    </script>
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
                        @include('partials.locale-flags')
                        <button type="button" @click="dark = !dark" class="auth-split-theme-btn" :aria-label="dark ? @json(__('auth.light_mode')) : @json(__('auth.dark_mode'))">
                            <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </button>
                    </div>
                </div>

                @yield('content')
            </div>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
