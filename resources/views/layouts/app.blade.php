<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full" data-loading-text="{{ __('common.loading') }}"
    x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
    x-init="$watch('dark', v => { localStorage.setItem('theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v) }); document.documentElement.classList.toggle('dark', dark)"
    :class="dark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | @yield('title', __('nav.dashboard'))</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    <script>
        (function(){var d=localStorage.getItem('theme')==='dark';if(d)document.documentElement.classList.add('dark')})();
    </script>
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="app-shell-page h-full text-slate-800 dark:text-zinc-200 antialiased"
    x-data="{ mobileSidebarOpen: false }">
@php $user = auth()->user(); @endphp
<div class="app-shell-ambient" aria-hidden="true">
    <span class="app-shell-orb app-shell-orb--violet"></span>
    <span class="app-shell-orb app-shell-orb--cyan"></span>
    <span class="app-shell-orb app-shell-orb--rose"></span>
</div>
<div class="app-shell min-h-full flex flex-col">
    <nav class="app-header sticky top-0 z-40 w-full h-16 flex items-center">
        <div class="mx-auto w-full px-4 sm:px-6 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button @click="mobileSidebarOpen = true" class="md:hidden p-2 text-slate-500 dark:text-zinc-300 hover:bg-indigo-500/10 rounded-lg transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
                <div class="flex items-center gap-3">
                    <div class="app-header-logo-wrap">
                        @include('partials.brand-logo', ['size' => 'header'])
                    </div>
                    <div>
                        <h1 class="app-header-title">{{ __('nav.welcome') }}</h1>
                        <p class="app-header-subtitle">{{ __('nav.platform') }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <button @click="dark = !dark" class="app-header-icon-btn" :title="dark ? '{{ __('common.light_mode') }}' : '{{ __('common.dark_mode') }}'">
                    <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                <div class="hidden sm:flex items-center">
                    @include('partials.locale-flags')
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="app-header-user-btn">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                            <p class="text-[10px] font-medium text-indigo-600 dark:text-indigo-300">{{ role_label($user->displayRole()) }}</p>
                        </div>
                        @include('partials.user-avatar', ['user' => $user])
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak class="app-header-dropdown absolute right-0 mt-2 w-56 rounded-2xl p-2 z-50">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="app-btn app-btn-link-danger app-btn-sm app-btn-block !justify-start">{{ __('nav.logout') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex flex-1 overflow-hidden min-h-0">
        <aside class="app-sidebar hidden md:flex flex-col w-64 p-4 space-y-6 overflow-y-auto transition-colors">
            @include('partials.sidebar')
        </aside>

        <div x-show="mobileSidebarOpen" x-cloak class="fixed inset-0 z-50 md:hidden" @keydown.escape.window="mobileSidebarOpen = false">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="mobileSidebarOpen = false"></div>
            <aside class="app-sidebar app-sidebar--mobile absolute left-0 top-0 bottom-0 w-72 p-4 overflow-y-auto">
                @include('partials.sidebar')
            </aside>
        </div>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto app-main">
            <div class="max-w-7xl mx-auto app-content-shell">
                @include('partials.flash-messages')
                @yield('content')
            </div>
        </main>
    </div>

    <footer class="app-footer">
        <p class="app-footer-text">{{ __('home.footer_copyright') }}</p>
    </footer>
</div>
@include('partials.document-viewer')
@vite(['resources/js/app.js'])
@stack('scripts')
</body>
</html>
