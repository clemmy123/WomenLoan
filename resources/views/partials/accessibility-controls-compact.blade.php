{{-- Compact accessibility block for profile menu --}}
<div class="app-a11y-compact">
    <div class="app-a11y-compact-row">
        <span class="app-a11y-compact-label">{{ __('accessibility.theme') }}</span>
        <div class="app-a11y-compact-segment" role="group" aria-label="{{ __('accessibility.theme') }}">
            <button type="button"
                    class="app-a11y-compact-btn"
                    :class="{ 'is-active': ! $store.a11y.dark }"
                    @click="$store.a11y.setDark(false)"
                    :title="@json(__('accessibility.light'))">
                @include('partials.icons.a11y-sun')
            </button>
            <button type="button"
                    class="app-a11y-compact-btn"
                    :class="{ 'is-active': $store.a11y.dark }"
                    @click="$store.a11y.setDark(true)"
                    :title="@json(__('accessibility.dark'))">
                @include('partials.icons.a11y-moon')
            </button>
        </div>
    </div>

    <div class="app-a11y-compact-row">
        <span class="app-a11y-compact-label">{{ __('accessibility.text_size') }}</span>
        <div class="app-a11y-compact-segment" role="group" aria-label="{{ __('accessibility.text_size') }}">
            <button type="button"
                    class="app-a11y-compact-btn app-a11y-compact-btn--text"
                    :class="{ 'is-active': $store.a11y.fontSize === 'normal' }"
                    @click="$store.a11y.setFontSize('normal')"
                    :title="@json(__('accessibility.text_normal'))">
                @include('partials.icons.a11y-text-a', ['size' => 'sm'])
            </button>
            <button type="button"
                    class="app-a11y-compact-btn app-a11y-compact-btn--text"
                    :class="{ 'is-active': $store.a11y.fontSize === 'large' }"
                    @click="$store.a11y.setFontSize('large')"
                    :title="@json(__('accessibility.text_large'))">
                @include('partials.icons.a11y-text-a', ['size' => 'md'])
            </button>
            <button type="button"
                    class="app-a11y-compact-btn app-a11y-compact-btn--text"
                    :class="{ 'is-active': $store.a11y.fontSize === 'xl' }"
                    @click="$store.a11y.setFontSize('xl')"
                    :title="@json(__('accessibility.text_xl'))">
                @include('partials.icons.a11y-text-a', ['size' => 'lg'])
            </button>
        </div>
    </div>

    <div class="app-a11y-compact-row app-a11y-compact-row--toggles">
        <div class="app-a11y-compact-toggle-item">
            <span class="app-a11y-compact-label">{{ __('accessibility.high_contrast') }}</span>
            <button type="button"
                    class="app-a11y-toggle app-a11y-toggle--compact"
                    role="switch"
                    :aria-checked="$store.a11y.highContrast"
                    :class="{ 'is-on': $store.a11y.highContrast }"
                    @click="$store.a11y.toggleHighContrast()">
                <span class="app-a11y-toggle-thumb" aria-hidden="true"></span>
            </button>
        </div>
        <div class="app-a11y-compact-toggle-item">
            <span class="app-a11y-compact-label">{{ __('accessibility.reduce_motion') }}</span>
            <button type="button"
                    class="app-a11y-toggle app-a11y-toggle--compact"
                    role="switch"
                    :aria-checked="$store.a11y.reduceMotion"
                    :class="{ 'is-on': $store.a11y.reduceMotion }"
                    @click="$store.a11y.toggleReduceMotion()">
                <span class="app-a11y-toggle-thumb" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</div>
