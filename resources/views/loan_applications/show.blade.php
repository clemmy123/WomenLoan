@extends('layouts.app')

@section('title', __('loans.show_title', ['track' => $loan->loan_track_id]))

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $loan->loan_track_id }}</h1>
            <p class="text-sm text-slate-500">{{ loan_type_label($loan->loan_type) }} — {{ __('common.step_n_of', ['step' => $loan->current_step, 'total' => 9]) }}</p>
        </div>
        @include('partials.loan-status-badge', ['status' => $loan->status])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900 mb-4">{{ __('loans.details') }}</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">{{ __('common.requested') }}</dt><dd class="font-semibold">{{ format_tzs($loan->requested_amount) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('common.proposed') }}</dt><dd class="font-semibold">{{ $loan->proposed_amount ? format_tzs($loan->proposed_amount) : '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('dashboard.disbursed') }}</dt><dd class="font-semibold">{{ $loan->disbursed_amount ? format_tzs($loan->disbursed_amount) : '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('dashboard.applicant') }}</dt><dd class="font-semibold">{{ $loan->applicant?->full_name ?? __('common.na') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('common.business') }}</dt><dd class="font-semibold">{{ $loan->businessDetails?->business_name ?? __('common.na') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('common.submitted') }}</dt><dd class="font-semibold">{{ $loan->created_at->translatedFormat('d M Y, H:i') }}</dd></div>
                </dl>
            </div>

            @if($loan->approvalLevels->count())
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900 mb-4">{{ __('loans.approval_history') }}</h3>
                <div class="space-y-3">
                    @foreach($loan->approvalLevels as $level)
                    <div class="flex gap-3 p-3 rounded-xl bg-slate-50 text-sm">
                        <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">{{ $level->step_number }}</div>
                        <div>
                            <p class="font-semibold">{{ workflow_action_label($level->action_taken) }}</p>
                            <p class="text-slate-500 text-xs">{{ $level->user?->name }} — {{ $level->created_at->translatedFormat('d M Y H:i') }}</p>
                            @if($level->comments)<p class="text-slate-600 mt-1">{{ $level->comments }}</p>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-4">
            @include('loan_applications._workflow_actions', ['loan' => $loan, 'accountants' => $accountants])
            <a href="{{ route('loan-applications.index') }}" class="block text-center text-sm text-slate-600 hover:text-indigo-600">← {{ __('common.back_to_list') }}</a>
        </div>
    </div>
</div>
@endsection
