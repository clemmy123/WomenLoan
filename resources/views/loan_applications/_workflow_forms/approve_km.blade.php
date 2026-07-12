<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="approve_km">
    <p class="text-sm text-slate-700 dark:text-zinc-300">{{ __('workflow.approve_confirm_message') }}</p>
    <div>
        <label class="app-label">{{ __('workflow.comments') }} @include('partials.required-mark')</label>
        <textarea name="comments" rows="3" required class="app-textarea" placeholder="{{ __('workflow.comments') }}"></textarea>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-success">{{ __('workflow.buttons.approve_km') }}</button>
    </div>
</form>
