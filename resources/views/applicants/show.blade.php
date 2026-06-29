@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('applicants.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-900 transition-colors">&larr; Back to Registry</a>
        <a href="{{ route('applicants.edit', $applicant) }}" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 transition-all">Edit Profile</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 bg-gray-50 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $applicant->full_name }}</h1>
                <p class="text-xs text-gray-500 font-mono mt-1">System Profile ID: #{{ $applicant->id }} &bull; Assigned to Admin ID: {{ $applicant->user_id }}</p>
            </div>
            <span class="mt-2 sm:mt-0 inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-800 ring-1 ring-inset ring-gray-600/20">
                Manual Mode (No NIDA Sync)
            </span>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">National Identification Number (NIN)</span>
                <span class="font-mono text-base tracking-wide text-gray-900">{{ $applicant->nin }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Date of Birth</span>
                <span class="text-base text-gray-900">{{ \Carbon\Carbon::parse($applicant->dob)->format('M d, Y') }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Phone Number</span>
                <span class="text-base font-semibold text-gray-900">{{ $applicant->phone }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Email Address</span>
                <span class="text-base text-gray-900">{{ $applicant->email }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Demographics</span>
                <span class="text-base text-gray-900">{{ $applicant->sex ?? 'Unspecified' }} &bull; {{ $applicant->marital_status ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Location & Nationality</span>
                <span class="text-base text-gray-900">{{ $applicant->location->name ?? 'None Assigned' }} ({{ $applicant->nationality }})</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">Linked Loan Groups</h3>
            
            <ul class="divide-y divide-gray-100">
                @forelse($applicant->groups as $group)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span class="font-semibold text-gray-900">{{ $group->name }}</span>
                        <form action="{{ route('applicants.detach-group', [$applicant->id, $group->id]) }}" method="POST" onsubmit="return confirm('Unlink this applicant from this group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-900 font-medium">Remove</button>
                        </form>
                    </li>
                @empty
                    <li class="py-4 text-center text-xs text-gray-400">This applicant is not registered to any group currently.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">Link Group Assignment</h3>
            
            <form action="{{ route('applicants.attach-group', $applicant) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="group_id" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Select Active System Group</label>
                    <input type="number" name="group_id" id="group_id" required placeholder="Enter Loan Group ID..." class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('group_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full bg-gray-900 text-white rounded-lg py-2 text-sm font-semibold hover:bg-gray-800 transition-all">
                    Link Pivot Association
                </button>
            </form>
        </div>
    </div>
</div>
@endsection