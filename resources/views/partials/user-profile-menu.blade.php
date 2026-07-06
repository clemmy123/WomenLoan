@php
    $profileUrl = null;
    $profileLabel = __('nav.my_profile');

    if ($user->applicant) {
        $profileUrl = route('applicants.show', $user->applicant);
    } elseif ($nav['registerApplicant'] ?? false) {
        $profileUrl = route('applicants.create');
        $profileLabel = __('nav.register_applicant');
    }
@endphp

<div class="relative"
     x-data="{ menuOpen: false, a11yOpen: false }"
     @keydown.escape.window="menuOpen = false; a11yOpen = false">
    <button type="button"
            @click="menuOpen = !menuOpen; if (!menuOpen) a11yOpen = false"
            :aria-expanded="menuOpen"
            aria-haspopup="dialog"
            class="app-header-user-btn">
        <div class="text-right hidden sm:block">
            <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
            <p class="text-[10px] font-medium text-indigo-600 dark:text-indigo-300">{{ role_label($user->displayRole()) }}</p>
        </div>
        @include('partials.user-avatar', ['user' => $user])
    </button>

    <div x-show="menuOpen"
         x-cloak
         x-transition.opacity.duration.150ms
         @click.outside="menuOpen = false; a11yOpen = false"
         class="app-profile-menu"
         role="dialog"
         aria-label="{{ __('nav.profile') }}"
         @click.stop>
        <div class="app-profile-menu-accent" aria-hidden="true"></div>
        <div class="app-profile-menu-hero">
            <div class="app-profile-menu-avatar">
                @include('partials.user-avatar', ['user' => $user, 'class' => 'h-14 w-14 text-base ring-4 ring-white/30 dark:ring-white/10'])
            </div>
            <p class="app-profile-menu-name">{{ $user->name }}</p>
            <p class="app-profile-menu-role">{{ role_label($user->displayRole()) }}</p>
            @if($user->email)
                <p class="app-profile-menu-email">{{ $user->email }}</p>
            @endif
        </div>

        <div class="app-profile-menu-body">
            <nav class="app-profile-menu-links" aria-label="{{ __('nav.profile') }}">
                @if($profileUrl)
                    <a href="{{ $profileUrl }}" class="app-profile-menu-link" @click="menuOpen = false; a11yOpen = false">
                        <span class="app-profile-menu-link-icon app-profile-menu-link-icon--profile">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <span>{{ $profileLabel }}</span>
                    </a>
                @endif

                <a href="{{ route('profile.password.edit') }}" class="app-profile-menu-link" @click="menuOpen = false; a11yOpen = false">
                    <span class="app-profile-menu-link-icon app-profile-menu-link-icon--password">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2"/>
                            <path stroke-linecap="round" d="M8 11V8a4 4 0 118 0v3"/>
                        </svg>
                    </span>
                    <span>{{ __('nav.change_password') }}</span>
                </a>
            </nav>

            <div class="app-profile-menu-a11y">
                <button type="button"
                        class="app-profile-menu-a11y-trigger"
                        @click="a11yOpen = !a11yOpen"
                        :aria-expanded="a11yOpen"
                        aria-controls="profile-menu-a11y-panel">
                    <span class="app-profile-menu-a11y-trigger-label">
                        @include('partials.icons.a11y-settings')
                        <span>{{ __('accessibility.title') }}</span>
                    </span>
                    <svg class="app-profile-menu-a11y-chevron" :class="{ 'is-open': a11yOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div id="profile-menu-a11y-panel"
                     x-show="a11yOpen"
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="app-profile-menu-a11y-panel">
                    @include('partials.accessibility-controls-compact')
                </div>
            </div>

            <div class="app-profile-menu-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="app-profile-menu-logout">
                        <span class="app-profile-menu-logout-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                            </svg>
                        </span>
                        <span>{{ __('nav.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
