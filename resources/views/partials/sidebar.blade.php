<nav class="space-y-1">
    @if($nav['viewDashboard'])
    <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2">{{ __('nav.dashboard') }}</p>
    <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'sidebar-active' : '' }}">
        <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3"/></svg>
        {{ __('nav.dashboard') }}
    </a>
    @endif

    @if($nav['trackLoan'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.track_loan') }}</p>
        <form action="{{ route('loans.track') }}" method="GET" class="px-4 mb-2">
            <input type="text" name="track_id" placeholder="WL000001" class="w-full text-xs rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-dm-850 text-slate-900 dark:text-zinc-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
        </form>
    @endif

    @if($nav['isApplicant'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.my_profile') }}</p>
        @if($user->applicant)
            <a href="{{ route('applicants.show', $user->applicant) }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('applicants.show') ? 'sidebar-active' : '' }}">
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ $user->applicant->full_name ?? __('nav.my_profile') }}
            </a>
        @elseif($nav['registerApplicant'])
            <a href="{{ route('applicants.create') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('applicants.create') ? 'sidebar-active' : '' }}">
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                {{ __('nav.register_applicant') }}
            </a>
        @endif

        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.loan_applications') }}</p>
        <a href="{{ route('loan-applications.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('loan-applications.index') ? 'sidebar-active' : '' }}">
            <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('nav.my_loans') }}
        </a>
        @if($nav['createLoan'])
            <a href="{{ route('loan-applications.create') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('loan-applications.create') ? 'sidebar-active' : '' }}">
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('nav.new_application') }}
            </a>
        @endif
    @endif

    @if($nav['manageApplicants'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.applicants') }}</p>
        <a href="{{ route('applicants.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('applicants.*') ? 'sidebar-active' : '' }}">
            <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            {{ __('nav.applicants') }}
        </a>
    @endif

    @if($nav['viewStaffLoans'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.pending_review') }}</p>
        <a href="{{ route('loan-applications.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('loan-applications.*') ? 'sidebar-active' : '' }}">
            <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            {{ __('nav.loan_applications') }}
        </a>
    @endif

    @if($nav['manageGroups'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.loan_groups') }}</p>
        <a href="{{ route('loan-groups.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('loan-groups.*') ? 'sidebar-active' : '' }}">
            <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ __('nav.loan_groups') }}
        </a>
    @endif

    @if($nav['viewRepayments'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.repayments') }}</p>
        <a href="{{ route('repayments.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('repayments.*') ? 'sidebar-active' : '' }}">
            <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            {{ __('nav.repayments') }}
        </a>
    @endif

    @if($nav['viewReports'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.reports') }}</p>
        <a href="{{ route('reports.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('reports.*') ? 'sidebar-active' : '' }}">
            <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            {{ __('nav.reports') }}
        </a>
    @endif

    @if($nav['manageUsers'] || $nav['manageRoles'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.administration') }}</p>
        @if($nav['manageUsers'])
            <a href="{{ route('admin.users.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'sidebar-active' : '' }}">
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                {{ __('nav.users') }}
            </a>
        @endif
        @if($nav['manageRoles'])
            <a href="{{ route('admin.roles.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.roles.*') ? 'sidebar-active' : '' }}">
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                {{ __('nav.roles') }}
            </a>
        @endif
    @endif
</nav>
