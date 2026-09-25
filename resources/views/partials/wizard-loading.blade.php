<div data-wizard-loading class="wizard-loading" hidden role="status" aria-live="polite">
    <div class="wizard-loading-box">
        <svg class="wizard-loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="wizard-loading-spinner-track" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="wizard-loading-spinner-head" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span data-wizard-loading-text class="wizard-loading-text">{{ $message ?? __('common.loading') }}</span>
    </div>
</div>
