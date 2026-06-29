@extends('layouts.app')

@section('title', __('groups.show_title'))

@section('content')
<div class="page">
    @include('partials.page-header', [
        'title' => $loanGroup->name,
        'subtitle' => __('groups.reg_number').': '.($loanGroup->registration_number ?? __('common.na')),
        'actions' => '<a href="'.e(route('loan-groups.index')).'" class="app-btn app-btn-secondary">'.e(__('groups.back_to_list')).'</a>'
            .'<a href="'.e(route('loan-groups.edit', $loanGroup)).'" class="app-btn app-btn-primary">'.e(__('groups.edit_profile')).'</a>',
    ])

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="app-card app-card-padded">
            <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">{{ __('groups.total_members') }}</p>
            <p class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $loanGroup->applicants->count() }}</p>
        </div>
        <div class="app-card app-card-padded">
            <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">{{ __('groups.total_loans') }}</p>
            <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $loanGroup->loans->count() }}</p>
        </div>
        <div class="app-card app-card-padded">
            <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">{{ __('groups.contact_phone') }}</p>
            <p class="text-lg font-bold text-slate-700 dark:text-zinc-200 mt-2">{{ $loanGroup->phone ?? __('groups.not_set') }}</p>
        </div>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h3 class="font-bold text-sm text-slate-800 dark:text-white">{{ __('groups.group_members') }}</h3>
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
                    <td class="text-sm font-semibold text-slate-700 dark:text-zinc-200">{{ $applicant->full_name }}</td>
                    <td class="text-sm text-slate-500 dark:text-zinc-400 font-mono">{{ $applicant->nin }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="app-table-empty italic">{{ __('groups.no_members') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h3 class="font-bold text-sm text-slate-800 dark:text-white">{{ __('groups.associated_loans') }}</h3>
        </div>
        <table class="app-table">
            <thead>
                <tr>
                    <th>{{ __('groups.track_id') }}</th>
                    <th>{{ __('common.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loanGroup->loans as $loan)
                <tr>
                    <td>
                        <a href="{{ route('loan-applications.show', $loan) }}" class="app-table-link">{{ $loan->loan_track_id }}</a>
                    </td>
                    <td>
                        @include('partials.loan-status-badge', ['status' => $loan->status])
                    </td>
                </tr>
                @empty
                <tr><td colspan="2" class="app-table-empty italic">{{ __('groups.no_loans') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
