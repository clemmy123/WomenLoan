<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full" data-loading-text="{{ __('common.loading') }}"
    x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
    x-init="$watch('dark', v => { localStorage.setItem('theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v) }); document.documentElement.classList.toggle('dark', dark)"
    :class="dark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('nav.welcome') }} | @yield('title', __('nav.dashboard'))</title>
    <script>
        (function(){var d=localStorage.getItem('theme')==='dark';if(d)document.documentElement.classList.add('dark')})();
    </script>
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="h-full text-slate-800 dark:text-zinc-200 bg-slate-50 dark:bg-black antialiased"
    x-data="{ mobileSidebarOpen: false }">
@php $user = auth()->user(); @endphp
<div class="min-h-full flex flex-col">
    <nav class="app-header sticky top-0 z-40 backdrop-blur-md border-b border-slate-200/60 dark:border-white/[0.06] w-full h-16 flex items-center">
        <div class="mx-auto w-full px-4 sm:px-6 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button @click="mobileSidebarOpen = true" class="md:hidden p-2 text-slate-500 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-lg">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
                <div class="flex items-center gap-3">
                    @include('partials.brand-logo', ['size' => 'header'])
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('nav.welcome') }}</h1>
                        <p class="text-[10px] text-slate-400 dark:text-zinc-500 uppercase tracking-widest">{{ __('nav.platform') }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <button @click="dark = !dark" class="p-2 rounded-xl border border-slate-200 dark:border-white/10 text-slate-500 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-white/5 transition" :title="dark ? '{{ __('common.light_mode') }}' : '{{ __('common.dark_mode') }}'">
                    <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                <div class="hidden sm:flex items-center gap-1 text-xs">
                    <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-indigo-100 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 font-semibold' : 'text-slate-500 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-white/5' }}">{{ __('nav.english') }}</a>
                    <a href="{{ route('locale.switch', 'sw') }}" class="px-2 py-1 rounded-lg {{ app()->getLocale() === 'sw' ? 'bg-indigo-100 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 font-semibold' : 'text-slate-500 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-white/5' }}">{{ __('nav.swahili') }}</a>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-3 pl-3 py-1 pr-1 rounded-full hover:bg-slate-100 dark:hover:bg-white/5 border border-slate-200 dark:border-white/10 transition">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                            <p class="text-[10px] text-indigo-600 dark:text-indigo-400">{{ role_label($user->displayRole()) }}</p>
                        </div>
                        @include('partials.user-avatar', ['user' => $user])
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-56 bg-white dark:dark-surface rounded-2xl border border-slate-200 dark:border-white/[0.08] shadow-lg dark:shadow-none p-2 z-50">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl">{{ __('nav.logout') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex flex-1 overflow-hidden">
        <aside class="app-sidebar hidden md:flex flex-col w-64 border-r border-slate-200/60 dark:border-white/[0.06] p-4 space-y-6 overflow-y-auto transition-colors">
            @include('partials.sidebar')
        </aside>

        <div x-show="mobileSidebarOpen" x-cloak class="fixed inset-0 z-50 md:hidden" @keydown.escape.window="mobileSidebarOpen = false">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="mobileSidebarOpen = false"></div>
            <aside class="app-sidebar absolute left-0 top-0 bottom-0 w-72 p-4 overflow-y-auto border-r border-slate-200/60 dark:border-white/[0.06]">
                @include('partials.sidebar')
            </aside>
        </div>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto dark-app-main">
            <div class="max-w-7xl mx-auto app-content-shell">
                @include('partials.flash-messages')
                @yield('content')
            </div>
        </main>
    </div>
</div>
@vite(['resources/js/app.js'])
@stack('scripts')
</body>
</html>
