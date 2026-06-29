<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WDF Core Platform | Full Management System</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-header { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(16px); }
        .sidebar-active { 
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); 
            color: white !important; 
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2); 
        }
    </style>
</head>

<body class="h-full text-slate-800 antialiased" x-data="{ mobileSidebarOpen: false }">

    <div class="min-h-full flex flex-col">
        <nav class="sticky top-0 z-40 glass-header border-b border-slate-200/60 w-full h-16 flex items-center shadow-sm">
            <div class="mx-auto w-full px-4 sm:px-6 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <button @click="mobileSidebarOpen = true" class="md:hidden p-2 text-slate-500 hover:bg-slate-100 rounded-lg">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 bg-gradient-to-tr from-indigo-600 to-violet-500 rounded-2xl flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-200">W</div>
                        <div>
                            <h1 class="text-sm font-bold text-slate-900 tracking-tight">WDF Core Platform</h1>
                            <p class="text-[10px] text-slate-400 font-mono tracking-widest uppercase">v2.1 Full Management</p>
                        </div>
                    </div>
                </div>


                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-3 pl-3 py-1 pr-1 rounded-full hover:bg-slate-100 transition-all border border-slate-200">
                        <span class="text-xs font-semibold hidden sm:block">System Admin</span>
                        <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff" class="h-9 w-9 rounded-full ring-2 ring-white">
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-slate-100 p-2 z-50">
                        <div class="px-4 py-2 border-b border-slate-100 mb-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Current Session</p>
                            <p class="text-xs font-semibold text-emerald-600">Testing Sandbox Mode</p>
                        </div>
                        <a href="#" class="block px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 rounded-xl">Profile Settings</a>
                        <hr class="my-1 border-slate-100">
                        <a href="#" class="block px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 rounded-xl">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex flex-1 overflow-hidden">
            <aside class="hidden md:flex flex-col w-64 bg-white border-r border-slate-200/60 p-4 space-y-8 overflow-y-auto">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-4 mb-3">Main</p>
                    <nav class="space-y-1 mb-2">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'sidebar-active' : 'text-slate-600 hover:bg-slate-100' }}">
                            <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3"/></svg>
                            Dashboard
                        </a>
                    </nav>

                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-4 mb-3">Core Registration</p>
                    <nav class="space-y-1">
                        <a href="{{ route('applicants.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all {{ request()->routeIs('applicants.index') ? 'sidebar-active' : 'text-slate-600 hover:bg-slate-100' }}">
                            <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            List Of Applicants
                        </a>
                        <a href="{{ route('applicants.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all {{ request()->routeIs('applicants.create') ? 'sidebar-active' : 'text-slate-600 hover:bg-slate-100' }}">
                            <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            Register Applicants
                        </a>
                    </nav>
                </div>

                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-4 mb-3">Loans Management</p>
                    <nav class="space-y-1">
                        <a href="{{ route('loan-applications.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all {{ request()->routeIs('loan-applications.*') ? 'sidebar-active' : 'text-slate-600 hover:bg-slate-100' }}">
                            <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Loan Applications
                        </a>
                        <a href="{{ route('loan-groups.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all {{ request()->routeIs('loan-groups.index') ? 'sidebar-active' : 'text-slate-600 hover:bg-slate-100' }}">
                            <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Active Loan Groups
                        </a>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium text-slate-600 hover:bg-slate-100">
                            <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Disbursement Logs
                        </a>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium text-slate-600 hover:bg-slate-100">
                            <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Repayment Matrix
                        </a>
                    </nav>
                </div>
            </aside>

            <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
                @if(session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 shadow-sm flex items-center gap-3">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success') }}
                    </div>
                @endif
                
                <div class="max-w-5xl mx-auto">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>