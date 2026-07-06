{{--
    Gradient "Are you sure?" confirm card.
    Requires parent Alpine scope. Pass $show (x-show expression), $close, $title, $message.
    Optional: $note, $confirmLabel, $cancelLabel, $confirmClick, $confirmVariant (primary|success|danger), $footer (raw HTML).
--}}
@php
    $confirmLabel = $confirmLabel ?? __('common.yes');
    $cancelLabel = $cancelLabel ?? __('common.cancel');
    $confirmVariant = $confirmVariant ?? 'primary';
    $confirmVariantClass = match ($confirmVariant) {
        'success' => 'app-confirm-modal-btn--success',
        'danger' => 'app-confirm-modal-btn--danger',
        default => 'app-confirm-modal-btn--ok',
    };
@endphp
<div
    x-show="{!! $show !!}"
    x-cloak
    class="app-modal-root"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="confirm-modal-title-{{ $id ?? $name ?? 'default' }}"
    @keydown.escape.window="{!! $close !!}"
>
    <div class="app-modal-backdrop" @click="{!! $close !!}"></div>
    <div class="app-confirm-modal-panel" @click.stop>
        <div class="app-confirm-modal-hero">
            <button
                type="button"
                class="app-confirm-modal-close"
                @click="{!! $close !!}"
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
        <div class="app-confirm-modal-body">
            <h3 id="confirm-modal-title-{{ $id ?? $name ?? 'default' }}" class="app-confirm-modal-title">{{ $title }}</h3>
            <p class="app-confirm-modal-message">{{ $message }}</p>
            @if(! empty($note))
                <p class="app-confirm-modal-note">{{ $note }}</p>
            @endif
            @if(! empty($body))
                <div class="app-confirm-modal-extra">{!! $body !!}</div>
            @endif
            @if(! empty($footer))
                {!! $footer !!}
            @elseif(! empty($confirmClick))
                <div class="app-confirm-modal-actions">
                    <button
                        type="button"
                        class="app-confirm-modal-btn {{ $confirmVariantClass }}"
                        @click="{!! $confirmClick !!}"
                    >{{ $confirmLabel }}</button>
                    <button
                        type="button"
                        class="app-confirm-modal-btn app-confirm-modal-btn--cancel"
                        @click="{!! $close !!}"
                    >{{ $cancelLabel }}</button>
                </div>
            @endif
        </div>
    </div>
</div>
