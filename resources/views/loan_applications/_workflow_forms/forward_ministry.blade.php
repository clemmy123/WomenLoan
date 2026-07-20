<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="forward_ministry">
    <div>
        <label class="app-label">{{ __('workflow.your_comment') }} @include('partials.required-mark')</label>
        <textarea name="comments" rows="3" required class="app-textarea" placeholder="{{ __('workflow.your_comment') }}"></textarea>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('workflow.buttons.forward_ministry') }}</button>
    </div>
</form>
