<form :action="deactivateUser?.url" method="POST" class="space-y-4">
    @csrf
    <p class="text-sm font-medium text-slate-800 dark:text-zinc-100" x-text="deactivateUser?.name"></p>

    <div>
        <label class="app-label" for="deactivation_reason">{{ __('admin.deactivation_reason') }} @include('partials.required-mark')</label>
        <textarea
            name="deactivation_reason"
            id="deactivation_reason"
            rows="4"
            required
            minlength="5"
            maxlength="1000"
            class="app-input"
            placeholder="{{ __('admin.deactivation_reason_placeholder') }}"
        >{{ old('deactivation_reason') }}</textarea>
        <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.deactivation_reason_hint') }}</p>
        @error('deactivation_reason')
            <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap gap-2 justify-end">
        <button type="button" class="app-btn app-btn-secondary" @click="modal = null">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-danger">{{ __('admin.deactivate_user_confirm') }}</button>
    </div>
</form>
