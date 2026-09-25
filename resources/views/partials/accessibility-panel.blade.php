@props(['variant' => 'header'])

@php
    $triggerClass = match ($variant) {
        'auth' => 'auth-split-theme-btn',
        'landing' => 'app-a11y-trigger app-a11y-trigger--landing',
        default => 'app-header-icon-btn',
    };
@endphp

<div class="dropdown app-a11y-dropdown">
    <button type="button"
            class="{{ $triggerClass }} dropdown-toggle"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            aria-haspopup="dialog"
            aria-label="{{ __('accessibility.open_settings') }}"
            title="{{ __('accessibility.title') }}">
        @include('partials.icons.a11y-settings')
    </button>

    <div class="dropdown-menu dropdown-menu-end app-a11y-panel" role="dialog" aria-label="{{ __('accessibility.title') }}">
        <p class="app-a11y-panel-title">
            @include('partials.icons.a11y-settings')
            <span>{{ __('accessibility.title') }}</span>
        </p>

        @include('partials.accessibility-controls')
    </div>
</div>
