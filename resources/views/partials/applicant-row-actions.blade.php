@props(['applicant'])

<div
    class="app-kebab"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="app-kebab-trigger"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="menu"
        aria-label="{{ __('common.actions') }}"
        title="{{ __('common.actions') }}"
    >
        <svg class="app-kebab-dots" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="12" cy="5" r="1.75"/>
            <circle cx="12" cy="12" r="1.75"/>
            <circle cx="12" cy="19" r="1.75"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.120ms
        @click.outside="open = false"
        class="app-kebab-menu"
        role="menu"
    >
        <a
            href="{{ route('applicants.show', $applicant) }}"
            class="app-kebab-item"
            role="menuitem"
            @click="open = false"
        >
            <span class="app-kebab-item-icon app-kebab-item-icon--view" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </span>
            <span>{{ __('common.view') }}</span>
        </a>

        <a
            href="{{ route('applicants.edit', $applicant) }}"
            class="app-kebab-item"
            role="menuitem"
            @click="open = false"
        >
            <span class="app-kebab-item-icon app-kebab-item-icon--edit" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </span>
            <span>{{ __('common.edit') }}</span>
        </a>

        @can('manage applicants')
            <form
                action="{{ route('applicants.destroy', $applicant) }}"
                method="POST"
                onsubmit="return confirm(@json(__('applicants.delete_confirm')));"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="app-kebab-item app-kebab-item--danger"
                    role="menuitem"
                    @click="open = false"
                >
                    <span class="app-kebab-item-icon app-kebab-item-icon--delete" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </span>
                    <span>{{ __('common.delete') }}</span>
                </button>
            </form>
        @endcan
    </div>
</div>
