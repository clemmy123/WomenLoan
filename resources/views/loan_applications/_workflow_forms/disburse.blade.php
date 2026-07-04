<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="disburse">
    <div class="app-gradient-confirm">
        <div class="app-gradient-confirm-inner">
            <p class="app-gradient-confirm-title">{{ __('workflow.disburse_amount_label') }}</p>
            <p class="app-gradient-confirm-amount">{{ format_tzs($loan->proposed_amount) }}</p>
            <p class="app-gradient-confirm-note">{{ __('workflow.disburse_amount_fixed') }}</p>
        </div>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-success">{{ __('workflow.buttons.disburse', ['amount' => format_tzs($loan->proposed_amount)]) }}</button>
    </div>
</form>
