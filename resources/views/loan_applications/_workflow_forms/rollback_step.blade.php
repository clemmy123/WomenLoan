<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="rollback_step">
    <p class="text-sm text-slate-600 dark:text-zinc-400">
        {{ $rollbackToApplicant ? __('workflow.rollback_to_applicant_help') : __('workflow.rollback_help') }}
    </p>
    <div>
        <label class="app-label">{{ __('workflow.rollback_reason') }} @include('partials.required-mark')</label>
        <textarea name="comments" rows="3" required class="app-textarea" placeholder="{{ __('workflow.rollback_reason') }}"></textarea>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-danger">{{ $rollbackLabel }}</button>
    </div>
</form>
