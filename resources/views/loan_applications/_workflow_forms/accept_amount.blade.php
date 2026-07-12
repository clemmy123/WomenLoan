<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="accept_amount">

    <div class="app-gradient-confirm">
        <div class="app-gradient-confirm-inner">
            <p class="app-gradient-confirm-title">{{ __('workflow.accept_amount_confirm_title') }}</p>
            <p class="app-gradient-confirm-message">{{ __('workflow.accept_amount_confirm_message') }}</p>
            <p class="app-gradient-confirm-amount">{{ format_tzs($loan->proposed_amount) }}</p>
            <p class="app-gradient-confirm-note">{{ __('workflow.accept_amount_confirm_note') }}</p>
        </div>
    </div>

    <div>
        <label class="app-label">{{ __('workflow.comments') }} @include('partials.required-mark')</label>
        <textarea name="comments" rows="3" required class="app-textarea" placeholder="{{ __('workflow.comments') }}"></textarea>
    </div>

    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-success">{{ __('workflow.buttons.accept_amount') }}</button>
    </div>
</form>
