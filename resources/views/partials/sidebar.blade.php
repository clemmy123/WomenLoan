@php $sid = $sidebarDomId ?? 'nav'; @endphp
<div class="sidebar-inner">
    <div class="sidebar-brand">
        <div class="sidebar-brand-logo-wrap">
            @include('partials.brand-logo', ['size' => 'sidebar'])
        </div>
        <p class="sidebar-brand-title">{{ __('nav.welcome') }}</p>
        @if($nav['viewDashboard'] ?? false)
            <a href="{{ route('dashboard') }}" class="sidebar-home-pill">{{ __('nav.home') }}</a>
        @endif
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-nav-block">
            @if($nav['viewDashboard'])
                <a href="{{ route('dashboard') }}" class="sidebar-link{{ request()->routeIs('dashboard') ? ' sidebar-active' : '' }}">
                    <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3"/></svg>
                    <span class="sidebar-link-label">{{ __('nav.dashboard') }}</span>
                </a>
            @endif

            @if($nav['isApplicant'])
                @if($nav['needsProfileCompletion'])
                    <a href="{{ route('applicants.create') }}" class="sidebar-link{{ request()->routeIs('applicants.create') ? ' sidebar-active' : '' }}">
                        <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="sidebar-link-label">{{ __('nav.register_applicant') }}</span>
                    </a>
                @else
                    <a href="{{ route('loan-applications.index') }}" class="sidebar-link{{ request()->routeIs('loan-applications.index', 'loan-applications.create') ? ' sidebar-active' : '' }}">
                        <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="sidebar-link-label">{{ __('nav.my_loans') }}</span>
                    </a>
                    @if($nav['trackLoan'])
                        <a href="{{ route('loans.track') }}" class="sidebar-link{{ request()->routeIs('loans.track') ? ' sidebar-active' : '' }}">
                            <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span class="sidebar-link-label">{{ __('nav.track_loan') }}</span>
                        </a>
                    @endif
                @endif
            @endif

            @if($nav['manageApplicants'] || $nav['viewStaffLoans'] || $nav['manageGroups'])
                @php
                    $applicationsMenuOpen = request()->routeIs('applicants.*')
                        || request()->routeIs('loan-applications.*')
                        || request()->routeIs('loan-groups.*');
                    $staffLoansLabel = match (true) {
                        $nav['isChief'] ?? false => __('nav.assign_accountant_queue'),
                        $nav['isAccountant'] ?? false => __('nav.my_disbursements'),
                        default => __('nav.applications'),
                    };
                @endphp
                <div class="sidebar-group">
                    <button
                        type="button"
                        class="sidebar-link sidebar-menu-toggle{{ $applicationsMenuOpen ? ' sidebar-menu-open' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $sid }}-applications"
                        aria-expanded="{{ $applicationsMenuOpen ? 'true' : 'false' }}"
                        aria-controls="{{ $sid }}-applications"
                    >
                        <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="sidebar-link-label">{{ __('nav.applications_menu') }}</span>
                        <svg class="sidebar-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="{{ $sid }}-applications" class="collapse sidebar-submenu sidebar-tree{{ $applicationsMenuOpen ? ' show' : '' }}">
                        @if($nav['manageApplicants'])
                            <a href="{{ route('applicants.index') }}" class="sidebar-link sidebar-sublink{{ request()->routeIs('applicants.*') ? ' sidebar-sublink-active' : '' }}">{{ __('nav.applicants') }}</a>
                        @endif
                        @if($nav['viewStaffLoans'])
                            <a href="{{ route('loan-applications.index') }}" class="sidebar-link sidebar-sublink{{ request()->routeIs('loan-applications.*') ? ' sidebar-sublink-active' : '' }}">{{ $staffLoansLabel }}</a>
                        @endif
                        @if($nav['manageGroups'])
                            <a href="{{ route('loan-groups.index') }}" class="sidebar-link sidebar-sublink{{ request()->routeIs('loan-groups.*') ? ' sidebar-sublink-active' : '' }}">{{ __('nav.loan_groups') }}</a>
                        @endif
                    </div>
                </div>
            @endif

            @if($nav['viewRepayments'])
                <a href="{{ route('repayments.index') }}" class="sidebar-link{{ request()->routeIs('repayments.*') ? ' sidebar-active' : '' }}">
                    <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <span class="sidebar-link-label">{{ __('nav.repayments') }}</span>
                </a>
            @endif
        </div>

        @if(($nav['viewGeneralReportsSection'] ?? false) || ($nav['viewReportsSection'] ?? false))
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
                $totalLoansMenuOpen = $byRegionActive
                    || $byTypeActive
                    || $bySectorActive
                    || $byBankActive
                    || $byMonthlyActive
                    || $byAgeActive;
            @endphp
            <div class="sidebar-nav-block">
                <div class="sidebar-group">
                    <button
                        type="button"
                        class="sidebar-link sidebar-menu-toggle{{ $reportsMenuOpen ? ' sidebar-menu-open' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $sid }}-reports"
                        aria-expanded="{{ $reportsMenuOpen ? 'true' : 'false' }}"
                        aria-controls="{{ $sid }}-reports"
                    >
                        <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="sidebar-link-label">{{ __('nav.reports_section') }}</span>
                        <svg class="sidebar-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="{{ $sid }}-reports" class="collapse sidebar-submenu sidebar-tree{{ $reportsMenuOpen ? ' show' : '' }}">
                        @if($nav['viewGeneralReports'] ?? false)
                            <a href="{{ route('reports.general.women.index') }}" class="sidebar-link sidebar-sublink{{ request()->routeIs('reports.general.women.*') ? ' sidebar-sublink-active' : '' }}">{{ __('nav.general_reports') }}</a>
                        @endif
                        @if($nav['viewReportsOverview'] ?? false)
                            <a href="{{ route('reports.index') }}" class="sidebar-link sidebar-sublink{{ $reportsOverviewActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.reports_overview') }}</a>
                        @endif
                        @if($nav['viewApplicationReports'] ?? false)
                            <a href="{{ route('reports.applications.index') }}" class="sidebar-link sidebar-sublink{{ $applicationReportsActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.application_reports') }}</a>
                        @endif
                        @if($nav['viewPaymentReports'] ?? false)
                            <a href="{{ route('reports.analytical.overview') }}" class="sidebar-link sidebar-sublink{{ $analyticalOverviewActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.analytical_overview') }}</a>
                        @endif
                        @if($nav['viewOutstandingReports'] ?? false)
                            <a href="{{ route('reports.analytical.outstanding') }}" class="sidebar-link sidebar-sublink{{ $analyticalOutstandingActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.analytical_outstanding') }}</a>
                        @endif
                        @if($nav['viewOverdueReports'] ?? false)
                            <a href="{{ route('reports.analytical.overdue') }}" class="sidebar-link sidebar-sublink{{ $analyticalOverdueActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.analytical_overdue') }}</a>
                        @endif

                        @if($nav['viewTotalLoansReports'] ?? false)
                            <div class="sidebar-group">
                                <button
                                    type="button"
                                    class="sidebar-link sidebar-sublink sidebar-nested-toggle{{ $totalLoansMenuOpen ? ' sidebar-nested-open' : '' }}"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $sid }}-total-loans"
                                    aria-expanded="{{ $totalLoansMenuOpen ? 'true' : 'false' }}"
                                    aria-controls="{{ $sid }}-total-loans"
                                >
                                    <span class="sidebar-link-label">{{ __('nav.total_loans') }}</span>
                                    <svg class="sidebar-chevron sidebar-chevron-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div id="{{ $sid }}-total-loans" class="collapse sidebar-nested-submenu sidebar-tree{{ $totalLoansMenuOpen ? ' show' : '' }}">
                                    @if($nav['viewByRegionReports'] ?? false)
                                        <a href="{{ route('reports.by-region.index') }}" class="sidebar-link sidebar-sublink sidebar-nested-link{{ $byRegionActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.by_region') }}</a>
                                    @endif
                                    @if($nav['viewByTypeReports'] ?? false)
                                        <a href="{{ route('reports.by-type.index') }}" class="sidebar-link sidebar-sublink sidebar-nested-link{{ $byTypeActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.by_types') }}</a>
                                    @endif
                                    @if($nav['viewBySectorReports'] ?? false)
                                        <a href="{{ route('reports.by-sector.index') }}" class="sidebar-link sidebar-sublink sidebar-nested-link{{ $bySectorActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.by_sectors') }}</a>
                                    @endif
                                    @if($nav['viewByBankReports'] ?? false)
                                        <a href="{{ route('reports.by-bank.index') }}" class="sidebar-link sidebar-sublink sidebar-nested-link{{ $byBankActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.by_banks') }}</a>
                                    @endif
                                    @if($nav['viewByMonthlyReports'] ?? false)
                                        <a href="{{ route('reports.by-monthly.index') }}" class="sidebar-link sidebar-sublink sidebar-nested-link{{ $byMonthlyActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.by_monthly') }}</a>
                                    @endif
                                    @if($nav['viewByAgeReports'] ?? false)
                                        <a href="{{ route('reports.by-age.index') }}" class="sidebar-link sidebar-sublink sidebar-nested-link{{ $byAgeActive ? ' sidebar-sublink-active' : '' }}">{{ __('nav.by_age') }}</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($nav['viewAdminDashboard'] || $nav['manageUsers'] || $nav['manageRoles'] || ($nav['viewAuditLogs'] ?? false))
            @php
                $adminMenuOpen = request()->routeIs('admin.*');
            @endphp
            <div class="sidebar-nav-block">
                <div class="sidebar-group">
                    <button
                        type="button"
                        class="sidebar-link sidebar-menu-toggle{{ $adminMenuOpen ? ' sidebar-menu-open' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $sid }}-admin"
                        aria-expanded="{{ $adminMenuOpen ? 'true' : 'false' }}"
                        aria-controls="{{ $sid }}-admin"
                    >
                        <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span class="sidebar-link-label">{{ __('nav.administration') }}</span>
                        <svg class="sidebar-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="{{ $sid }}-admin" class="collapse sidebar-submenu sidebar-tree{{ $adminMenuOpen ? ' show' : '' }}">
                        @if($nav['viewAdminDashboard'] ?? false)
                            <a href="{{ route('admin.dashboard') }}" class="sidebar-link sidebar-sublink{{ request()->routeIs('admin.dashboard') ? ' sidebar-sublink-active' : '' }}">{{ __('nav.admin_dashboard') }}</a>
                        @endif
                        @if($nav['manageUsers'])
                            <a href="{{ route('admin.users.index') }}" class="sidebar-link sidebar-sublink{{ request()->routeIs('admin.users.*') ? ' sidebar-sublink-active' : '' }}">{{ __('nav.users') }}</a>
                        @endif
                        @if($nav['manageRoles'])
                            <a href="{{ route('admin.roles.index') }}" class="sidebar-link sidebar-sublink{{ request()->routeIs('admin.roles.*') ? ' sidebar-sublink-active' : '' }}">{{ __('nav.roles') }}</a>
                        @endif
                        @if($nav['viewAuditLogs'] ?? false)
                            <a href="{{ route('admin.audit.index') }}" class="sidebar-link sidebar-sublink{{ request()->routeIs('admin.audit.*') ? ' sidebar-sublink-active' : '' }}">{{ __('nav.audit_logs') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </nav>
</div>
