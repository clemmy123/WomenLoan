<form method="POST" action="{{ route('loans.workflow', $loan) }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="forward_council">
    <div>
        <label class="app-label">{{ __('workflow.review_comments') }} @include('partials.required-mark')</label>
        <textarea name="comments" rows="3" required class="app-textarea" placeholder="{{ __('workflow.comments') }}"></textarea>
    </div>
    <x-document-upload
        name="attachment"
        :title="__('workflow.supervision_document')"
        :required="true"
    />
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('workflow.buttons.forward_council') }}</button>
    </div>
</form>
