<form method="POST" action="{{ route('loans.workflow', $loan) }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="forward_ministry">
    <div>
        <label class="app-label">{{ __('workflow.review_comments') }}</label>
        <textarea name="comments" rows="3" class="app-textarea" placeholder="{{ __('workflow.review_comments') }}"></textarea>
    </div>
    <div>
        <label class="app-label">{{ __('common.attachment') }}</label>
        <input type="file" name="attachment" class="app-input text-sm">
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('workflow.buttons.forward_ministry') }}</button>
    </div>
</form>
