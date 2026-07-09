{{-- Requires parent x-data with `modal` property. Pass $name and $title. Body via $body. --}}
@php
    $wide = $wide ?? false;
@endphp
<div
    x-show="modal === @js($name)"
    x-cloak
    class="app-modal-root"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title-{{ $name }}"
    @keydown.escape.window="modal = null"
>
    <div class="app-modal-backdrop" @click="modal = null"></div>
    <div class="app-confirm-modal-panel{{ $wide ? ' app-confirm-modal-panel--wide' : '' }}" @click.stop>
        <div class="app-confirm-modal-hero">
            <button
                type="button"
                class="app-confirm-modal-close"
                @click="modal = null"
                aria-label="{{ __('common.close') }}"
            >&times;</button>
            <div class="app-confirm-modal-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round">
                    <circle cx="12" cy="12" r="9.25"/>
                    <path d="M9.5 9.25a2.75 2.75 0 0 1 5 1.35c0 1.85-2.75 2.15-2.75 3.9"/>
                    <circle cx="12" cy="17.15" r="0.85" fill="currentColor" stroke="none"/>
                </svg>
            </div>
        </div>
        <div class="app-confirm-modal-body app-confirm-modal-body--form">
            @if(! empty($title))
                <h3 id="modal-title-{{ $name }}" class="app-confirm-modal-title">{{ $title }}</h3>
            @endif
            @if(! empty($message))
                <p class="app-confirm-modal-message">{{ $message }}</p>
            @endif
            <div class="app-confirm-modal-extra">
                {!! $body !!}
            </div>
        </div>
    </div>
</div>
