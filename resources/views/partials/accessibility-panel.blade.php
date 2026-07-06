@props(['variant' => 'header'])

@php
    $triggerClass = match ($variant) {
        'auth' => 'auth-split-theme-btn',
        'landing' => 'app-a11y-trigger app-a11y-trigger--landing',
        default => 'app-header-icon-btn',
    };
@endphp

<div class="app-a11y-dropdown"
     x-data="{ open: false }"
     @click.outside="open = false"
     @keydown.escape.window="open = false">
    <button type="button"
            class="{{ $triggerClass }}"
            @click="open = !open"
            :aria-expanded="open"
            aria-haspopup="dialog"
            :aria-label="@json(__('accessibility.open_settings'))"
            :title="@json(__('accessibility.title'))">
        @include('partials.icons.a11y-settings')
    </button>

    <div class="app-a11y-panel"
         x-show="open"
         x-cloak
         x-transition.opacity.duration.150ms
         role="dialog"
         aria-label="{{ __('accessibility.title') }}"
         @click.stop>
        <p class="app-a11y-panel-title">
            @include('partials.icons.a11y-settings')
            <span>{{ __('accessibility.title') }}</span>
        </p>

        @include('partials.accessibility-controls')
    </div>
</div>
