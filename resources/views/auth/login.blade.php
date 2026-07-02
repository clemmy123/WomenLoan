@extends('layouts.guest')

@section('content')
<div class="bg-white dark:dark-surface rounded-3xl border border-slate-100 dark:border-white/[0.08] p-8">
    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ __('nav.login') }}</h2>

    @if($errors->any())
        <div class="mb-4 w-fit max-w-full rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 px-4 py-2.5 text-sm text-red-700 dark:text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="app-label" for="email">{{ __('common.email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus class="app-input">
        </div>
        <div x-data="{ showPassword: false }">
            <label class="app-label" for="password">{{ __('common.password') }}</label>
            <div class="relative">
                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required
                    class="app-input pr-11">
                <button type="button" @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300 transition"
                    :aria-label="showPassword ? @json(__('auth.hide_password')) : @json(__('auth.show_password'))">
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>
        <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-zinc-400">
            <input type="checkbox" name="remember" class="rounded border-slate-300 dark:border-white/20 text-indigo-600">
            {{ __('common.remember_me') }}
        </label>
        <button type="submit" data-loading-text="{{ __('auth.authenticating') }}"
            class="app-btn app-btn-primary app-btn-block">
            {{ __('nav.login') }}
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-500 dark:text-zinc-400">
        {{ __('auth.register_prompt') }}
        <a href="{{ route('register') }}" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">{{ __('nav.register') }}</a>
    </p>
</div>
@endsection
