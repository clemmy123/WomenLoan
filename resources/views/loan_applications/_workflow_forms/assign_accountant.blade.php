<form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="action" value="assign_accountant">
    <div>
        <label class="app-label">{{ __('workflow.select_accountant') }}</label>
        <select name="accountant_id" required class="app-select">
            <option value="">{{ __('workflow.select_accountant') }}</option>
            @foreach($accountants as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('workflow.buttons.assign_accountant') }}</button>
    </div>
</form>
