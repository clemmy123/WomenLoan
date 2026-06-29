@extends('layouts.app')

@section('title', __('nav.repayments'))

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('nav.repayments') }}</h1>
</div>
<div class="app-card overflow-hidden">
    <table class="app-table">
        <thead>
            <tr>
                <th>{{ __('repayments.loan') }}</th>
                <th>{{ __('repayments.disbursed_col') }}</th>
                <th>{{ __('repayments.outstanding') }}</th>
                <th>{{ __('repayments.progress') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            <tr>
                <td class="font-mono text-xs text-indigo-600">{{ $payment->loan?->loan_track_id }}</td>
                <td>{{ format_tzs($payment->amount_disbursed) }}</td>
                <td>{{ format_tzs($payment->outstanding_debt) }}</td>
                <td>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-slate-100 dark:bg-dm-800 rounded-full overflow-hidden max-w-[100px]">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $payment->repayment_progress_percentage }}%"></div>
                        </div>
                        <span class="text-xs font-semibold">{{ $payment->repayment_progress_percentage }}%</span>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="app-table-empty">{{ __('repayments.no_records') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="app-card-footer">{{ $payments->links() }}</div>
</div>
@endsection
