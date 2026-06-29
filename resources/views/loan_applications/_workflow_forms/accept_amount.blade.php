<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="accept_amount">
    <p class="text-sm text-slate-600 dark:text-zinc-400">{!! __('workflow.proposed_amount', ['amount' => '<strong>'.e(format_tzs($loan->proposed_amount)).'</strong>']) !!}</p>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-success">{{ __('workflow.buttons.accept_amount') }}</button>
    </div>
</form>
