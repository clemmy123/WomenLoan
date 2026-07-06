<div class="app-a11y-section">
    <div class="app-a11y-section-head">
        @include('partials.icons.a11y-theme')
        <span class="app-a11y-label">{{ __('accessibility.theme') }}</span>
    </div>
    <div class="app-a11y-segment" role="group" aria-label="{{ __('accessibility.theme') }}">
        <button type="button"
                class="app-a11y-segment-btn"
                :class="{ 'is-active': ! $store.a11y.dark }"
                @click="$store.a11y.setDark(false)">
            @include('partials.icons.a11y-sun')
            <span>{{ __('accessibility.light') }}</span>
        </button>
        <button type="button"
                class="app-a11y-segment-btn"
                :class="{ 'is-active': $store.a11y.dark }"
                @click="$store.a11y.setDark(true)">
            @include('partials.icons.a11y-moon')
            <span>{{ __('accessibility.dark') }}</span>
        </button>
    </div>
</div>

<div class="app-a11y-section">
    <div class="app-a11y-section-head">
        @include('partials.icons.a11y-text-size')
        <span class="app-a11y-label">{{ __('accessibility.text_size') }}</span>
    </div>
    <div class="app-a11y-segment" role="group" aria-label="{{ __('accessibility.text_size') }}">
        <button type="button"
                class="app-a11y-segment-btn"
                :class="{ 'is-active': $store.a11y.fontSize === 'normal' }"
                @click="$store.a11y.setFontSize('normal')">
            @include('partials.icons.a11y-text-a', ['size' => 'sm'])
            <span>{{ __('accessibility.text_normal') }}</span>
        </button>
        <button type="button"
                class="app-a11y-segment-btn"
                :class="{ 'is-active': $store.a11y.fontSize === 'large' }"
                @click="$store.a11y.setFontSize('large')">
            @include('partials.icons.a11y-text-a', ['size' => 'md'])
            <span>{{ __('accessibility.text_large') }}</span>
        </button>
        <button type="button"
                class="app-a11y-segment-btn"
                :class="{ 'is-active': $store.a11y.fontSize === 'xl' }"
                @click="$store.a11y.setFontSize('xl')">
            @include('partials.icons.a11y-text-a', ['size' => 'lg'])
            <span>{{ __('accessibility.text_xl') }}</span>
        </button>
    </div>
</div>

<div class="app-a11y-section app-a11y-section--toggle">
    <div class="app-a11y-section-head">
        @include('partials.icons.a11y-contrast')
        <span class="app-a11y-label">{{ __('accessibility.high_contrast') }}</span>
    </div>
    <button type="button"
            class="app-a11y-toggle"
            role="switch"
            :aria-checked="$store.a11y.highContrast"
            :class="{ 'is-on': $store.a11y.highContrast }"
            @click="$store.a11y.toggleHighContrast()">
        <span class="app-a11y-toggle-thumb" aria-hidden="true"></span>
    </button>
</div>

<div class="app-a11y-section app-a11y-section--toggle">
    <div class="app-a11y-section-head">
        @include('partials.icons.a11y-motion')
        <span class="app-a11y-label">{{ __('accessibility.reduce_motion') }}</span>
    </div>
    <button type="button"
            class="app-a11y-toggle"
            role="switch"
            :aria-checked="$store.a11y.reduceMotion"
            :class="{ 'is-on': $store.a11y.reduceMotion }"
            @click="$store.a11y.toggleReduceMotion()">
        <span class="app-a11y-toggle-thumb" aria-hidden="true"></span>
    </button>
</div>
