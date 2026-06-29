<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="disburse">
    <div>
        <label class="app-label">{{ __('dashboard.disbursed') }}</label>
        <input type="number" name="disbursed_amount" value="{{ $loan->proposed_amount }}" required class="app-input">
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-success">{{ __('workflow.buttons.disburse', ['amount' => format_tzs($loan->proposed_amount)]) }}</button>
    </div>
</form>
