@php
    $confirmLabel = $confirmLabel ?? __('common.yes');
    $cancelLabel = $cancelLabel ?? __('common.cancel');
    $confirmVariant = $confirmVariant ?? 'primary';
    $confirmVariantClass = match ($confirmVariant) {
        'success' => 'app-confirm-modal-btn--success',
        'danger' => 'app-confirm-modal-btn--danger',
        default => 'app-confirm-modal-btn--ok',
    };
    $modalId = $id ?? $name ?? 'default';
@endphp
<div
    class="app-modal-root"
    id="confirm-modal-{{ $modalId }}"
    hidden
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="confirm-modal-title-{{ $modalId }}"
    aria-hidden="true"
>
    <div class="app-modal-backdrop" data-close-modal></div>
    <div class="app-confirm-modal-panel">
        <div class="app-confirm-modal-hero">
            <button
                type="button"
                class="app-confirm-modal-close"
                data-close-modal
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
            <h3 id="confirm-modal-title-{{ $modalId }}" class="app-confirm-modal-title">{{ $title }}</h3>
            <p class="app-confirm-modal-message">{{ $message }}</p>
            @if(! empty($note))
                <p class="app-confirm-modal-note">{{ $note }}</p>
            @endif
            @if(! empty($body))
                <div class="app-confirm-modal-extra">{!! $body !!}</div>
            @endif
            @if(! empty($footer))
                {!! $footer !!}
            @else
                <div class="app-confirm-modal-actions">
                    @if(! empty($confirmFormAction))
                        <form method="POST" action="{{ $confirmFormAction }}">
                            @csrf
                            @if(! empty($confirmMethod))
                                @method($confirmMethod)
                            @endif
                            <button type="submit" class="app-confirm-modal-btn {{ $confirmVariantClass }}">{{ $confirmLabel }}</button>
                        </form>
                    @elseif(! empty($confirmButtonAttrs))
                        <button type="button" class="app-confirm-modal-btn {{ $confirmVariantClass }}" {!! $confirmButtonAttrs !!}>{{ $confirmLabel }}</button>
                    @endif
                    <button type="button" class="app-confirm-modal-btn app-confirm-modal-btn--cancel" data-close-modal>{{ $cancelLabel }}</button>
                </div>
            @endif
        </div>
    </div>
</div>
