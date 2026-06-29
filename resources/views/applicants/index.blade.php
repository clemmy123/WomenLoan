@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Applicants Registry</h1>
            <p class="mt-2 text-sm text-gray-600">A unified overview list of all profiles migrated directly into core manual system models.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('applicants.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all">
                Add New Applicant
            </a>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <form action="{{ route('applicants.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-grow">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Full Name, NIN, Phone or Email..." class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-gray-800 transition-all">Filter</button>
                @if(request('search'))
                    <a href="{{ route('applicants.index') }}" class="bg-gray-100 text-gray-700 border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium hover:bg-gray-200 transition-all flex items-center">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 tracking-wider uppercase">
                        <th class="px-6 py-4">Full Name</th>
                        <th class="px-6 py-4">NIN</th>
                        <th class="px-6 py-4">Contact Info</th>
                        <th class="px-6 py-4">Metrics</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                    @forelse($applicants as $applicant)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $applicant->full_name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $applicant->sex ?? 'Unspecified' }} &bull; {{ $applicant->marital_status ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-600 tracking-wider">
                                {{ $applicant->nin }}
                            </td>
                            <td class="px-6 py-4 space-y-0.5">
                                <div class="text-gray-900 font-medium">{{ $applicant->phone }}</div>
                                <div class="text-xs text-gray-500">{{ $applicant->email }}</div>
                            </td>
                            <td class="px-6 py-4 space-x-2">
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                    {{ $applicant->loans_count }} Loans
                                </span>
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                    {{ $applicant->groups_count }} Groups
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-xs space-x-2 whitespace-nowrap">
                                <a href="{{ route('applicants.show', $applicant) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">View</a>
                                <a href="{{ route('applicants.edit', $applicant) }}" class="text-amber-600 hover:text-amber-900 font-semibold">Edit</a>
                                <form action="{{ route('applicants.destroy', $applicant) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to drop this structural record row?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="text-base font-medium">No system registry matches found.</div>
                                <div class="text-xs text-gray-400 mt-1">Try modifying your query tags or create an applicant payload row profile above.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($applicants->hasPages())
            <div class="bg-white border-t border-gray-200 px-6 py-4">
                {{ $applicants->links() }}
            </div>
        @endif
    </div>
</div>
@endsection