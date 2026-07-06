<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="propose_amount">
    <div>
        <label class="app-label">{{ __('workflow.proposed_amount_placeholder') }} @include('partials.required-mark')</label>
        @include('partials.inputs.amount-input', [
            'name' => 'proposed_amount',
            'required' => true,
            'placeholder' => '0',
        ])
    </div>
    <div>
        <label class="app-label">{{ __('workflow.comments') }}</label>
        <textarea name="comments" rows="3" class="app-textarea" placeholder="{{ __('workflow.comments') }}"></textarea>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('workflow.buttons.propose_amount') }}</button>
    </div>
</form>
