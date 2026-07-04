<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full" data-loading-text="{{ __('common.loading') }}"
    x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
    x-init="$watch('dark', v => { localStorage.setItem('theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v) }); document.documentElement.classList.toggle('dark', dark)"
    :class="dark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | {{ __('nav.login') }}</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    <script>
        (function(){var d=localStorage.getItem('theme')==='dark';if(d)document.documentElement.classList.add('dark')})();
    </script>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full flex items-center justify-center p-6 bg-slate-50 dark:dark-auth-bg">
    <div class="w-full max-w-md">
        <div class="flex justify-end mb-4">
            <button @click="dark = !dark" class="p-2 rounded-xl border border-slate-200 dark:border-white/10 text-slate-500 dark:text-zinc-400 hover:bg-white dark:hover:bg-white/5">
                <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>
        <div class="text-center mb-6">
            <div class="mb-3 flex justify-center">
                @include('partials.brand-logo', ['size' => 'auth'])
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('nav.welcome') }}</h1>
            <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('nav.platform') }}</p>
        </div>
        @yield('content')
        <div class="mt-6 flex justify-center">
            @include('partials.locale-flags')
        </div>
    </div>
    @vite(['resources/js/app.js'])
</body>
</html>
