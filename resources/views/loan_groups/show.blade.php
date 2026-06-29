@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    {{-- HEADER SECTION --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $loanGroup->name }}</h2>
            <p class="text-sm text-slate-500 font-mono">Reg: {{ $loanGroup->registration_number ?? 'N/A' }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('loan-groups.index') }}" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50">Back to List</a>
            <a href="{{ route('loan-groups.edit', $loanGroup) }}" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-bold hover:bg-indigo-100">Edit Profile</a>
        </div>
    </div>

    {{-- STATS GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Members</p>
            <p class="text-3xl font-black text-indigo-600 mt-1">{{ $loanGroup->applicants->count() }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Loans</p>
            <p class="text-3xl font-black text-emerald-600 mt-1">{{ $loanGroup->loans->count() }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Contact Phone</p>
            <p class="text-lg font-bold text-slate-700 mt-2">{{ $loanGroup->phone ?? 'Not set' }}</p>
        </div>
    </div>

    {{-- MEMBERS TABLE --}}
    <div class="bg-white border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 font-bold text-sm text-slate-800">Group Members</div>
        <table class="w-full text-left">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Full Name</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">NIN</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($loanGroup->applicants as $applicant)
                <tr>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ $applicant->full_name }}</td>
                    <td class="px-6 py-4 text-sm text-slate-500 font-mono">{{ $applicant->nin }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="px-6 py-4 text-sm text-slate-400 italic">No members found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- LOANS TABLE --}}
    <div class="bg-white border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 font-bold text-sm text-slate-800">Associated Loan Applications</div>
        <table class="w-full text-left">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Track ID</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($loanGroup->loans as $loan)
                <tr>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ $loan->loan_track_id }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-bold uppercase">{{ $loan->status }}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('loan-applications.show', $loan->id) }}" class="text-indigo-600 text-xs font-bold hover:underline">View Details</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-6 py-4 text-sm text-slate-400 italic">No loans found for this group.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection