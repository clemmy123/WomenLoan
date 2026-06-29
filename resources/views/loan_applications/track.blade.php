@extends('layouts.app')

@section('title', __('nav.track_loan'))

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">{{ __('nav.track_loan') }}: {{ $loan->loan_track_id }}</h1>
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <div class="flex justify-between">
            <span class="text-slate-500">{{ __('common.status') }}</span>
            @include('partials.loan-status-badge', ['status' => $loan->status])
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">{{ __('loans.current_step') }}</span>
            @include('partials.badge', ['variant' => 'secondary', 'text' => $loan->current_step.' / 9'])
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">{{ __('common.requested') }}</span>
            <span class="font-semibold">{{ format_tzs($loan->requested_amount) }}</span>
        </div>
        <a href="{{ route('loan-applications.show', $loan) }}" class="block text-center bg-indigo-600 text-white py-2 rounded-xl text-sm font-semibold">{{ __('loans.view_full_details') }}</a>
    </div>
</div>
@endsection
