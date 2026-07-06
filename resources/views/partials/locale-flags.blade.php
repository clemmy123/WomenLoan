@php
    $locale = app()->getLocale();
    $isEn = $locale === 'en';
    $redirect = request()->getRequestUri();
@endphp
<div class="app-locale-dropdown"
     x-data="{ open: false }"
     @click.outside="open = false"
     @keydown.escape.window="open = false">
    <button type="button"
            class="app-locale-dropdown-trigger"
            @click="open = !open"
            :aria-expanded="open"
            aria-haspopup="listbox"
            aria-label="{{ __('nav.language') }}">
        <span class="app-locale-dropdown-current">
            @if($isEn)
                @include('partials.flags.uk')
            @else
                @include('partials.flags.tanzania')
            @endif
            <span class="app-locale-code">{{ $isEn ? 'EN' : 'SW' }}</span>
        </span>
        <svg class="app-locale-dropdown-chevron" :class="{ 'is-open': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
        </svg>
    </button>
    <div class="app-locale-dropdown-menu"
         x-show="open"
         x-cloak
         x-transition.opacity.duration.150ms
         role="listbox"
         aria-label="{{ __('nav.language') }}">
        <a href="{{ route('locale.switch', ['locale' => 'en', 'redirect' => $redirect]) }}"
           data-locale-switch
           role="option"
           aria-selected="{{ $isEn ? 'true' : 'false' }}"
           title="{{ __('nav.english') }}"
           class="app-locale-dropdown-option {{ $isEn ? 'is-active' : '' }}"
           @click="open = false">
            @include('partials.flags.uk')
            <span class="app-locale-code">EN</span>
        </a>
        <a href="{{ route('locale.switch', ['locale' => 'sw', 'redirect' => $redirect]) }}"
           data-locale-switch
           role="option"
           aria-selected="{{ $isEn ? 'false' : 'true' }}"
           title="{{ __('nav.swahili') }}"
           class="app-locale-dropdown-option {{ $isEn ? '' : 'is-active' }}"
           @click="open = false">
            @include('partials.flags.tanzania')
            <span class="app-locale-code">SW</span>
        </a>
    </div>
</div>
