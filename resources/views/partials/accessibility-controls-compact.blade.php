<div class="app-a11y-compact">
    <div class="app-a11y-compact-row">
        <span class="app-a11y-compact-label">{{ __('accessibility.theme') }}</span>
        <div class="app-a11y-compact-segment" role="group" aria-label="{{ __('accessibility.theme') }}">
            <button type="button" class="app-a11y-compact-btn" data-a11y-dark="false" title="{{ __('accessibility.light') }}">
                @include('partials.icons.a11y-sun')
            </button>
            <button type="button" class="app-a11y-compact-btn" data-a11y-dark="true" title="{{ __('accessibility.dark') }}">
                @include('partials.icons.a11y-moon')
            </button>
        </div>
    </div>

    <div class="app-a11y-compact-row">
        <span class="app-a11y-compact-label">{{ __('accessibility.text_size') }}</span>
        <div class="app-a11y-compact-segment" role="group" aria-label="{{ __('accessibility.text_size') }}">
            <button type="button" class="app-a11y-compact-btn app-a11y-compact-btn--text" data-a11y-font="normal" title="{{ __('accessibility.text_normal') }}">
                @include('partials.icons.a11y-text-a', ['size' => 'sm'])
            </button>
            <button type="button" class="app-a11y-compact-btn app-a11y-compact-btn--text" data-a11y-font="large" title="{{ __('accessibility.text_large') }}">
                @include('partials.icons.a11y-text-a', ['size' => 'md'])
            </button>
            <button type="button" class="app-a11y-compact-btn app-a11y-compact-btn--text" data-a11y-font="xl" title="{{ __('accessibility.text_xl') }}">
                @include('partials.icons.a11y-text-a', ['size' => 'lg'])
            </button>
        </div>
    </div>

    <div class="app-a11y-compact-row app-a11y-compact-row--toggles">
        <div class="app-a11y-compact-toggle-item">
            <span class="app-a11y-compact-label">{{ __('accessibility.high_contrast') }}</span>
            <button type="button" class="app-a11y-toggle app-a11y-toggle--compact" role="switch" aria-checked="false" data-a11y-contrast>
                <span class="app-a11y-toggle-thumb" aria-hidden="true"></span>
            </button>
        </div>
        <div class="app-a11y-compact-toggle-item">
            <span class="app-a11y-compact-label">{{ __('accessibility.reduce_motion') }}</span>
            <button type="button" class="app-a11y-toggle app-a11y-toggle--compact" role="switch" aria-checked="false" data-a11y-motion>
                <span class="app-a11y-toggle-thumb" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</div>
