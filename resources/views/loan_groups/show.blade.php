@extends('layouts.app')

@section('title', __('groups.show_title'))

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $loanGroup->name }}</h2>
            <p class="text-sm text-slate-500 font-mono">{{ __('groups.reg_number') }}: {{ $loanGroup->registration_number ?? __('common.na') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('loan-groups.index') }}" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50">{{ __('groups.back_to_list') }}</a>
            <a href="{{ route('loan-groups.edit', $loanGroup) }}" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-bold hover:bg-indigo-100">{{ __('groups.edit_profile') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="app-card app-card-padded">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.total_members') }}</p>
            <p class="text-3xl font-black text-indigo-600 mt-1">{{ $loanGroup->applicants->count() }}</p>
        </div>
        <div class="app-card app-card-padded">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.total_loans') }}</p>
            <p class="text-3xl font-black text-emerald-600 mt-1">{{ $loanGroup->loans->count() }}</p>
        </div>
        <div class="app-card app-card-padded">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.contact_phone') }}</p>
            <p class="text-lg font-bold text-slate-700 mt-2">{{ $loanGroup->phone ?? __('groups.not_set') }}</p>
        </div>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h3 class="font-bold text-sm text-slate-800">{{ __('groups.group_members') }}</h3>
        </div>
        <table class="app-table">
            <thead>
                <tr>
                    <th>{{ __('common.full_name') }}</th>
                    <th>{{ __('applicants.nin') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loanGroup->applicants as $applicant)
                <tr>
                    <td class="text-sm font-semibold text-slate-700">{{ $applicant->full_name }}</td>
                    <td class="text-sm text-slate-500 font-mono">{{ $applicant->nin }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="app-table-empty italic">{{ __('groups.no_members') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h3 class="font-bold text-sm text-slate-800">{{ __('groups.associated_loans') }}</h3>
        </div>
        <table class="app-table">
            <thead>
                <tr>
                    <th>{{ __('groups.track_id') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-right">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loanGroup->loans as $loan)
                <tr>
                    <td class="text-sm font-semibold text-slate-700">{{ $loan->loan_track_id }}</td>
                    <td class="text-sm">
                        @include('partials.loan-status-badge', ['status' => $loan->status])
                    </td>
                    <td class="text-right">
                        <div class="inline-flex items-center justify-end">
                            @include('partials.table-icon', ['action' => 'view', 'href' => route('loan-applications.show', $loan), 'label' => __('groups.view_details')])
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="app-table-empty italic">{{ __('groups.no_loans') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
