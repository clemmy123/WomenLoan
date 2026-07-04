<div class="app-locale-flags flex items-center gap-1.5">
    <a href="{{ route('locale.switch', ['locale' => 'en', 'redirect' => request()->getRequestUri()]) }}"
       data-locale-switch
       title="{{ __('nav.english') }}"
       aria-label="{{ __('nav.english') }}"
       class="app-locale-flag {{ app()->getLocale() === 'en' ? 'is-active' : '' }}">
        @include('partials.flags.uk')
    </a>
    <a href="{{ route('locale.switch', ['locale' => 'sw', 'redirect' => request()->getRequestUri()]) }}"
       data-locale-switch
       title="{{ __('nav.swahili') }}"
       aria-label="{{ __('nav.swahili') }}"
       class="app-locale-flag {{ app()->getLocale() === 'sw' ? 'is-active' : '' }}">
        @include('partials.flags.tanzania')
    </a>
</div>
