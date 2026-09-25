<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" translate="no" class="notranslate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google" content="notranslate">
    <title>{{ __('nav.welcome') }} | @yield('title', __('nav.dashboard'))</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    @include('partials.accessibility-head-script')
    @include('partials.assets-head')
    @stack('head')
</head>
<body class="app-shell-page" data-loading-text="{{ __('common.loading') }}">
@php $user = auth()->user(); @endphp
<div class="app-shell-ambient" aria-hidden="true">
    <span class="app-shell-orb app-shell-orb--violet"></span>
    <span class="app-shell-orb app-shell-orb--cyan"></span>
    <span class="app-shell-orb app-shell-orb--rose"></span>
</div>
<div class="app-shell">
    <aside class="app-sidebar d-none d-md-flex">
        @include('partials.sidebar', ['sidebarDomId' => 'desk'])
    </aside>

    <div class="app-shell-stage">
        <nav class="app-header">
            <div class="app-header-inner">
                <div class="app-header-left">
                    <button
                        type="button"
                        class="app-header-menu-btn d-md-none"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#appMobileSidebar"
                        aria-controls="appMobileSidebar"
                        aria-label="{{ __('common.menu') }}"
                    >
                        <svg class="app-header-menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    </button>
                </div>

                <div class="app-header-right">
                    @include('partials.locale-flags')
                    @include('partials.user-profile-menu')
                </div>
            </div>
        </nav>

        <div class="app-shell-body">
            <div class="offcanvas offcanvas-start app-offcanvas d-md-none" tabindex="-1" id="appMobileSidebar" aria-labelledby="appMobileSidebarLabel">
                <div class="offcanvas-header sidebar-offcanvas-header">
                    <h2 class="visually-hidden" id="appMobileSidebarLabel">{{ __('nav.welcome') }}</h2>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="offcanvas" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="offcanvas-body p-0">
                    @include('partials.sidebar', ['sidebarDomId' => 'mob'])
                </div>
            </div>

            <main class="app-main">
                @include('partials.flash-messages')
                <div class="app-content-shell">
                    @yield('content')
                </div>
            </main>
        </div>

        <footer class="app-footer">
            <p class="app-footer-text">{{ __('home.footer_copyright') }}</p>
        </footer>
    </div>
</div>
@include('partials.document-viewer')
@include('partials.assets-body')
@stack('scripts')
</body>
</html>
