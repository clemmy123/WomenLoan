{{-- Requires parent x-data with `modal` property. Pass $name and $title. Body via $body or default slot in @component --}}
<div
    x-show="modal === @js($name)"
    x-cloak
    class="app-modal-root"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="modal = null"
>
    <div class="app-modal-backdrop" @click="modal = null"></div>
    <div class="app-modal-panel" @click.stop>
        <div class="app-modal-header">
            <h3 class="app-modal-title">{{ $title }}</h3>
            <button type="button" class="app-modal-close" @click="modal = null" aria-label="{{ __('common.cancel') }}">&times;</button>
        </div>
        <div class="app-modal-body">
            {!! $body !!}
        </div>
    </div>
</div>
