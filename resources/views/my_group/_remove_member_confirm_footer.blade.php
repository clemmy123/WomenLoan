<form method="POST" class="app-confirm-modal-actions" data-remove-member-form>
    @csrf
    @method('DELETE')
    <button type="submit" class="app-confirm-modal-btn app-confirm-modal-btn--danger">{{ __('groups.remove_member') }}</button>
    <button type="button" class="app-confirm-modal-btn app-confirm-modal-btn--cancel" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
</form>
