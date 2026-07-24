@props(['user'])

@php
    $canDeactivate = auth()->user()?->can('deactivate users') && $user->is_active && $user->id !== auth()->id();
    $canActivate = auth()->user()?->can('activate users') && ! $user->is_active;
@endphp

<div
    class="app-kebab"
    x-data="appKebab"
    @keydown.escape.window="close()"
    @scroll.window="close()"
    @resize.window="open && placeMenu()"
    @mousedown.window="onOutside($event)"
>
    <button
        type="button"
        class="app-kebab-trigger"
        x-ref="trigger"
        @click="toggle()"
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

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-ref="menu"
            x-transition.opacity.duration.120ms
            class="app-kebab-menu app-kebab-menu--portal"
            :style="menuStyle"
            role="menu"
        >
            <a
                href="{{ route('admin.users.show', $user) }}"
                class="app-kebab-item"
                role="menuitem"
                @click="close()"
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
                href="{{ route('admin.users.edit', $user) }}"
                class="app-kebab-item"
                role="menuitem"
                @click="close()"
            >
                <span class="app-kebab-item-icon app-kebab-item-icon--edit" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </span>
                <span>{{ __('common.edit') }}</span>
            </a>

            <a
                href="{{ route('admin.users.assign-roles', $user) }}"
                class="app-kebab-item"
                role="menuitem"
                @click="close()"
            >
                <span class="app-kebab-item-icon app-kebab-item-icon--roles" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </span>
                <span>{{ __('admin.assign_roles') }}</span>
            </a>

            @if ($canDeactivate)
                <button
                    type="button"
                    class="app-kebab-item app-kebab-item--danger"
                    role="menuitem"
                    @click="close(); $dispatch('user-deactivate', {
                        name: {{ \Illuminate\Support\Js::from($user->name) }},
                        url: {{ \Illuminate\Support\Js::from(route('admin.users.deactivate', $user)) }}
                    })"
                >
                    <span class="app-kebab-item-icon app-kebab-item-icon--delete" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </span>
                    <span>{{ __('admin.deactivate_user') }}</span>
                </button>
            @endif

            @if ($canActivate)
                <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                    @csrf
                    <button
                        type="submit"
                        class="app-kebab-item"
                        role="menuitem"
                        @click="close()"
                    >
                        <span class="app-kebab-item-icon app-kebab-item-icon--roles" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <span>{{ __('admin.activate_user') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </template>
</div>
