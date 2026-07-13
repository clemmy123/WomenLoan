<nav class="flex-1 min-h-0 space-y-1">
    @if($nav['viewDashboard'])
    <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2">{{ __('nav.dashboard') }}</p>
    <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'sidebar-active' : '' }}">
        <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3"/></svg>
        {{ __('nav.dashboard') }}
    </a>
    @endif

    @if($nav['isApplicant'])
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.loan_applications') }}</p>
        <a href="{{ route('loan-applications.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('loan-applications.index') ? 'sidebar-active' : '' }}">
            <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('nav.my_loans') }}
        </a>
        @if($nav['createLoan'])
            <a href="{{ route('loan-applications.create') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('loan-applications.create') ? 'sidebar-active' : '' }}">
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ $nav['newApplicationLabel'] }}
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
        @php
            $staffLoansSection = match (true) {
                $nav['isChief'] ?? false => __('nav.approved_for_assignment'),
                $nav['isAccountant'] ?? false => __('nav.disbursements'),
                default => __('nav.pending_review'),
            };
            $staffLoansLabel = match (true) {
                $nav['isChief'] ?? false => __('nav.assign_accountant_queue'),
                $nav['isAccountant'] ?? false => __('nav.my_disbursements'),
                default => __('nav.loan_applications'),
            };
        @endphp
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ $staffLoansSection }}</p>
        <a href="{{ route('loan-applications.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('loan-applications.*') ? 'sidebar-active' : '' }}">
            <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            {{ $staffLoansLabel }}
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

    @if($nav['viewReportsSection'] ?? false)
        @php
            $reportsMenuOpen = request()->routeIs('reports.*');
            $reportsOverviewActive = request()->routeIs('reports.index', 'reports.export.*');
            $applicationReportsActive = request()->routeIs('reports.applications.*');
            $analyticalOverviewActive = request()->routeIs('reports.analytical.overview', 'reports.analytical.export.*');
            $analyticalOutstandingActive = request()->routeIs('reports.analytical.outstanding*');
            $analyticalOverdueActive = request()->routeIs('reports.analytical.overdue*');
            $byRegionActive = request()->routeIs('reports.by-region.*');
            $byTypeActive = request()->routeIs('reports.by-type.*');
            $bySectorActive = request()->routeIs('reports.by-sector.*');
            $byBankActive = request()->routeIs('reports.by-bank.*');
            $byMonthlyActive = request()->routeIs('reports.by-monthly.*');
            $byAgeActive = request()->routeIs('reports.by-age.*');
        @endphp
        <div class="mt-4 space-y-1" x-data="{ open: {{ $reportsMenuOpen ? 'true' : 'false' }} }">
            <button
                type="button"
                @click="open = !open"
                class="sidebar-link sidebar-menu-toggle flex w-full items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ $reportsMenuOpen ? 'sidebar-menu-open' : '' }}"
                :aria-expanded="open.toString()"
            >
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="flex-1 text-left">{{ __('nav.reports_section') }}</span>
                <svg class="h-4 w-4 opacity-60 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="sidebar-submenu space-y-0.5 pl-3 ml-5 border-l border-slate-200 dark:border-white/10"
            >
                @if($nav['viewReportsOverview'] ?? false)
                    <a href="{{ route('reports.index') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $reportsOverviewActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.reports_overview') }}
                    </a>
                @endif
                @if($nav['viewApplicationReports'] ?? false)
                    <a href="{{ route('reports.applications.index') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $applicationReportsActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.application_reports') }}
                    </a>
                @endif
                @if($nav['viewPaymentReports'] ?? false)
                    <a href="{{ route('reports.analytical.overview') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $analyticalOverviewActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.analytical_overview') }}
                    </a>
                @endif
                @if($nav['viewOutstandingReports'] ?? false)
                    <a href="{{ route('reports.analytical.outstanding') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $analyticalOutstandingActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.analytical_outstanding') }}
                    </a>
                @endif
                @if($nav['viewOverdueReports'] ?? false)
                    <a href="{{ route('reports.analytical.overdue') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $analyticalOverdueActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.analytical_overdue') }}
                    </a>
                @endif
                @if($nav['viewByRegionReports'] ?? false)
                    <a href="{{ route('reports.by-region.index') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $byRegionActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.by_region') }}
                    </a>
                @endif
                @if($nav['viewByTypeReports'] ?? false)
                    <a href="{{ route('reports.by-type.index') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $byTypeActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.by_types') }}
                    </a>
                @endif
                @if($nav['viewBySectorReports'] ?? false)
                    <a href="{{ route('reports.by-sector.index') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $bySectorActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.by_sectors') }}
                    </a>
                @endif
                @if($nav['viewByBankReports'] ?? false)
                    <a href="{{ route('reports.by-bank.index') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $byBankActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.by_banks') }}
                    </a>
                @endif
                @if($nav['viewByMonthlyReports'] ?? false)
                    <a href="{{ route('reports.by-monthly.index') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $byMonthlyActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.by_monthly') }}
                    </a>
                @endif
                @if($nav['viewByAgeReports'] ?? false)
                    <a href="{{ route('reports.by-age.index') }}" class="sidebar-link sidebar-sublink block px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $byAgeActive ? 'sidebar-sublink-active' : '' }}">
                        {{ __('nav.by_age') }}
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($nav['viewAdminDashboard'] || $nav['manageUsers'] || $nav['manageRoles'] || ($nav['viewAuditLogs'] ?? false))
        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest px-4 mb-2 mt-4">{{ __('nav.administration') }}</p>
        @if($nav['viewAdminDashboard'] ?? false)
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'sidebar-active' : '' }}">
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                {{ __('nav.admin_dashboard') }}
            </a>
        @endif
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
        @if($nav['viewAuditLogs'] ?? false)
            <a href="{{ route('admin.audit.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.audit.*') ? 'sidebar-active' : '' }}">
                <svg class="h-4 w-4 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                {{ __('nav.audit_logs') }}
            </a>
        @endif
    @endif
</nav>
