@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="p-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Loans Dashboard</h1>
                <p class="text-slate-600 mt-1">Monitor loan applications, approvals, balances, and repayment.</p>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <span class="text-sm font-semibold text-slate-700">Loan Trends</span>
                <button type="button" class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600">
                    Apply Filter
                </button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <a href="{{ route('dashboard') }}" class="rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-400 p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm font-medium text-blue-50">Total Applications</p>
                <p class="mt-3 text-3xl font-semibold text-white">1,248</p>
                <p class="mt-2 text-sm text-blue-100">+8.2% this month</p>
            </a>
            <a href="{{ route('dashboard') }}" class="rounded-2xl border border-slate-200 bg-gradient-to-br from-violet-600 via-fuchsia-500 to-pink-400 p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm font-medium text-violet-50">Approved Loans</p>
                <p class="mt-3 text-3xl font-semibold text-white">842</p>
                <p class="mt-2 text-sm text-violet-100">Steady approval rate</p>
            </a>
            <a href="{{ route('dashboard') }}" class="rounded-2xl border border-slate-200 bg-gradient-to-br from-emerald-600 via-teal-500 to-cyan-400 p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm font-medium text-emerald-50">Outstanding Balance</p>
                <p class="mt-3 text-3xl font-semibold text-white">$3.8M</p>
                <p class="mt-2 text-sm text-emerald-100">+2.7% this quarter</p>
            </a>
            <a href="{{ route('dashboard') }}" class="rounded-2xl border border-slate-200 bg-gradient-to-br from-rose-600 via-red-500 to-orange-400 p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm font-medium text-rose-50">Delinquent Accounts</p>
                <p class="mt-3 text-3xl font-semibold text-white">24</p>
                <p class="mt-2 text-sm text-rose-100">3 new follow-ups</p>
            </a>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Portfolio summary</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">This month at a glance</h2>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">Live</span>
                </div>
                <div class="mt-6 space-y-4 text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>New applications</span>
                        <strong class="text-slate-900">186</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Loans disbursed</span>
                        <strong class="text-slate-900">94</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Average interest rate</span>
                        <strong class="text-slate-900">11.8%</strong>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Performance graph</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Loan portfolio trend</h2>
                    </div>
                    <span class="text-sm font-medium text-slate-500">Updated 5m ago</span>
                </div>
                <div class="mt-6">
                    <div class="h-64 overflow-hidden rounded-3xl border border-slate-100 bg-slate-50 p-5">
                        <svg viewBox="0 0 500 250" class="h-full w-full">
                            <defs>
                                <linearGradient id="bar-gradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#60a5fa" />
                                    <stop offset="100%" stop-color="#38bdf8" />
                                </linearGradient>
                            </defs>
                            <rect x="50" y="120" width="40" height="110" fill="url(#bar-gradient)" rx="8" />
                            <rect x="120" y="90" width="40" height="140" fill="url(#bar-gradient)" rx="8" />
                            <rect x="190" y="70" width="40" height="160" fill="url(#bar-gradient)" rx="8" />
                            <rect x="260" y="110" width="40" height="120" fill="url(#bar-gradient)" rx="8" />
                            <rect x="330" y="80" width="40" height="150" fill="url(#bar-gradient)" rx="8" />
                            <rect x="400" y="100" width="40" height="130" fill="url(#bar-gradient)" rx="8" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Channel mix</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Loan source breakdown</h2>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-center">
                    <div class="relative h-56 w-56">
                        <svg viewBox="0 0 42 42" class="h-full w-full">
                            <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="#cbd5e1" stroke-width="7" />
                            <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="#2563eb" stroke-width="7" stroke-dasharray="25 75" stroke-dashoffset="25" transform="rotate(-90 21 21)" />
                            <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="#22c55e" stroke-width="7" stroke-dasharray="20 80" stroke-dashoffset="0" transform="rotate(25 21 21)" />
                            <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="#f97316" stroke-width="7" stroke-dasharray="15 85" stroke-dashoffset="45" transform="rotate(105 21 21)" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center text-center">
                            <div>
                                <p class="text-xl font-semibold text-slate-900">45%</p>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Top channel</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 grid gap-3 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span> Direct</span>
                        <strong class="text-slate-900">45%</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Referral</span>
                        <strong class="text-slate-900">30%</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> Partners</span>
                        <strong class="text-slate-900">25%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
