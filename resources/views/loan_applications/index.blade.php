@extends('layouts.app')

@section('title', 'My Loan Applications')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Page Header --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900">Loan Applications</h2>
        <p class="text-slate-500 mt-1">View submitted applications or resume saved drafts.</p>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl text-emerald-800 text-sm shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Submitted Loans --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h3 class="font-bold text-lg mb-4">Submitted Applications</h3>
        @if($loans->count())
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600">
                        <th class="p-3 text-left">Track ID</th>
                        <th class="p-3 text-left">Type</th>
                        <th class="p-3 text-left">Amount</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Submitted</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr class="border-t">
                            <td class="p-3">{{ $loan->loan_track_id }}</td>
                            <td class="p-3 capitalize">{{ $loan->loan_type }}</td>
                            <td class="p-3">{{ number_format($loan->requested_amount) }}</td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs font-bold 
                                    {{ $loan->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </td>
                            <td class="p-3">{{ $loan->created_at->format('d M Y') }}</td>
                            <td class="p-3">
                                <a href="{{ route('loan-applications.show', $loan->id) }}" class="text-indigo-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $loans->links() }}
            </div>
        @else
            <p class="text-slate-500">No applications submitted yet.</p>
        @endif
    </div>

    {{-- Draft Loans --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h3 class="font-bold text-lg mb-4">Saved Drafts</h3>
        @if($drafts->count())
            <ul class="space-y-3">
                @foreach($drafts as $draft)
                    <li class="flex justify-between items-center border p-3 rounded-xl">
                        <div>
                            <p class="font-bold text-slate-800">Track ID: {{ $draft->track_id }}</p>
                            <p class="text-slate-500 text-sm">Saved on {{ $draft->updated_at->format('d M Y, H:i') }}</p>
                        </div>
                        <a href="{{ route('loan-applications.create', ['resume_track_id' => $draft->track_id]) }}" 
                           class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 text-sm font-bold">
                            Resume
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-slate-500">No drafts saved yet.</p>
        @endif
    </div>

    {{-- New Application Button --}}
    <div class="text-center">
        <a href="{{ route('loan-applications.create') }}" 
           class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700">
            Start New Application
        </a>
    </div>
</div>
@endsection
