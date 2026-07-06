<form :action="member?.destroy_url" method="POST" class="app-confirm-modal-actions">
    @csrf
    @method('DELETE')
    <button type="submit" class="app-confirm-modal-btn app-confirm-modal-btn--danger">{{ __('groups.remove_member') }}</button>
    <button type="button" class="app-confirm-modal-btn app-confirm-modal-btn--cancel" @click="modal = null">{{ __('common.cancel') }}</button>
</form>
