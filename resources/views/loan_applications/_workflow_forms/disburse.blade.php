@php
    $defaultGrace = (int) config('wdf.grace_period_months', 3);
@endphp
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
    <div>
        <label class="app-label" for="grace_period_months">{{ __('workflow.grace_period_months') }}</label>
        <input
            type="number"
            name="grace_period_months"
            id="grace_period_months"
            value="{{ old('grace_period_months', $defaultGrace) }}"
            min="0"
            max="12"
            required
            class="app-input"
        >
        <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">{{ __('workflow.grace_period_help') }}</p>
        @error('grace_period_months')
            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="app-label">{{ __('workflow.comments') }} @include('partials.required-mark')</label>
        <textarea name="comments" rows="3" required class="app-textarea" placeholder="{{ __('workflow.comments') }}"></textarea>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-success">{{ __('workflow.buttons.disburse', ['amount' => format_tzs($loan->proposed_amount)]) }}</button>
    </div>
</form>
