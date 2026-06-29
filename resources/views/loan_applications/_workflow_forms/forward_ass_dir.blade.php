<form method="POST" action="{{ route('loans.workflow', $loan) }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="forward_ass_dir">
    <div>
        <label class="app-label">{{ __('workflow.comments') }}</label>
        <textarea name="comments" rows="3" class="app-textarea"></textarea>
    </div>
    <div>
        <label class="app-label">{{ __('common.attachment') }}</label>
        <input type="file" name="attachment" class="app-input text-sm">
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('workflow.buttons.forward_ass_dir') }}</button>
    </div>
</form>
