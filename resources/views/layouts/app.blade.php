<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full" data-loading-text="{{ __('common.loading') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | @yield('title', __('nav.dashboard'))</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    @include('partials.accessibility-head-script')
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
<div class="app-shell">
    <nav class="app-header shrink-0 z-40 w-full h-16 flex items-center">
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
                @include('partials.locale-flags')
                @include('partials.user-profile-menu')
            </div>
        </div>
    </nav>

    <div class="app-shell-body">
        <aside class="app-sidebar hidden md:flex">
            @include('partials.sidebar')
        </aside>

        <div x-show="mobileSidebarOpen" x-cloak class="fixed inset-0 z-50 md:hidden" @keydown.escape.window="mobileSidebarOpen = false">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="mobileSidebarOpen = false"></div>
            <aside class="app-sidebar app-sidebar--mobile absolute left-0 top-0 bottom-0 w-72 p-4 overflow-y-auto">
                @include('partials.sidebar')
            </aside>
        </div>

        <main class="app-main">
            @include('partials.flash-messages')
            <div class="max-w-7xl mx-auto app-content-shell p-6 lg:p-10">
                @yield('content')
            </div>
        </main>
    </div>

    <footer class="app-footer shrink-0">
        <p class="app-footer-text">{{ __('home.footer_copyright') }}</p>
    </footer>
</div>
@include('partials.document-viewer')
@vite(['resources/js/app.js'])
@stack('scripts')
</body>
</html>
