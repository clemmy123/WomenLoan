@extends('layouts.app')

@section('title', __('nav.repayments'))

@section('content')
<div class="page">
    @include('partials.page-header', ['title' => __('nav.repayments')])
<div class="app-card overflow-hidden">
    <table class="app-table">
        <thead>
            <tr>
                <th>{{ __('repayments.loan') }}</th>
                <th>{{ __('repayments.disbursed_col') }}</th>
                <th>{{ __('repayments.outstanding') }}</th>
                <th>{{ __('repayments.progress') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            <tr>
                <td>
                    <a href="{{ route('repayments.show', $payment) }}" class="font-mono text-xs font-semibold text-indigo-600 hover:underline">
                        {{ $payment->loan?->loan_track_id }}
                    </a>
                </td>
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
                <td>
                    <a href="{{ route('repayments.show', $payment) }}" class="app-btn app-btn-secondary text-xs">{{ __('repayments.view_schedule') }}</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="app-table-empty">{{ __('repayments.no_records') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="app-card-footer">{{ $payments->links() }}</div>
</div>
@endsection
