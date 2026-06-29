@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Active Loan Groups</h2>
            <p class="text-sm text-slate-500">Manage all your registered loan groups in the system.</p>
        </div>
        <a href="{{ route('loan-groups.create') }}" class="inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-2xl text-sm font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Register New Group
        </a>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm">
        <form action="{{ route('loan-groups.index') }}" method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search groups by name or reg number..." class="flex-1 bg-slate-50 border-0 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <button type="submit" class="bg-slate-900 text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-slate-800 transition-all">Search</button>
        </form>
    </div>

    <div class="bg-white border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Group Name</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Reg Number</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Members</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($loanGroups as $group)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-slate-900">{{ $group->name }}</p>
                            <p class="text-[11px] text-slate-400">{{ $group->phone }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm font-mono text-slate-600">{{ $group->registration_number }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700">
                                {{ $group->applicants_count ?? 0 }} Members
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('loan-groups.show', $group->id) }}" class="text-indigo-600 font-bold text-xs hover:underline">View Details</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">No loan groups found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($loanGroups->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $loanGroups->links() }}
        </div>
        @endif
    </div>
</div>
@endsection