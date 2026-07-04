<div id="doc-viewer-modal" class="doc-viewer-modal" hidden aria-hidden="true">
    <div class="doc-viewer-backdrop" data-doc-viewer-close></div>
    <div class="doc-viewer-panel" role="dialog" aria-modal="true" aria-labelledby="doc-viewer-title">
        <div class="doc-viewer-header">
            <h3 id="doc-viewer-title" class="doc-viewer-title"></h3>
            <div class="doc-viewer-actions">
                <a href="#" target="_blank" rel="noopener noreferrer" class="doc-viewer-open-tab" data-doc-viewer-open hidden>
                    {{ __('common.open_in_new_tab') }}
                </a>
                <button type="button" class="doc-viewer-close" data-doc-viewer-close aria-label="{{ __('common.close') }}">&times;</button>
            </div>
        </div>
        <div class="doc-viewer-body">
            <iframe class="doc-viewer-frame" title="{{ __('common.view_document') }}" hidden></iframe>
            <div class="doc-viewer-fallback" hidden>
                <p class="doc-viewer-fallback-text">{{ __('common.preview_not_available') }}</p>
                <a href="#" target="_blank" rel="noopener noreferrer" class="doc-viewer-fallback-link app-btn app-btn-primary" data-doc-viewer-fallback-link>
                    {{ __('common.open_document') }}
                </a>
            </div>
        </div>
    </div>
</div>
