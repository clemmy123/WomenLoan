@props(['loan'])

<div class="app-row-actions">
    <a
        href="{{ route('loan-applications.show', $loan) }}"
        class="app-icon-btn app-icon-btn--view"
        title="{{ __('common.view') }}"
        aria-label="{{ __('common.view') }}"
    >
        @include('partials.icons.eye')
    </a>
    @if($loan->isEditableByApplicant())
        <a
            href="{{ route('loan-applications.edit', $loan) }}"
            class="app-icon-btn app-icon-btn--accent"
            title="{{ __('common.edit') }}"
            aria-label="{{ __('common.edit') }}"
        >
            @include('partials.icons.pencil')
        </a>
    @endif
</div>
