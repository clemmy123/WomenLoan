<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate h-full" data-loading-text="{{ __('common.loading') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | {{ __('nav.login') }}</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    @include('partials.accessibility-head-script')
    @vite(['resources/css/app.css'])
</head>
<body class="h-full flex items-center justify-center p-6 bg-slate-50 dark:dark-auth-bg">
    <div class="w-full max-w-md">
        <div class="flex justify-end gap-2 mb-4">
            @include('partials.accessibility-panel')
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
