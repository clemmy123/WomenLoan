@extends('layouts.app')

@section('title', __('repayments.payments_title'))

@section('content')
<div class="page">
    @include('partials.page-header', ['title' => __('repayments.payments_title')])
<div class="app-card overflow-hidden">
    <table class="app-table">
        <thead>
            <tr>
                <th>{{ __('repayments.loan') }}</th>
                <th>{{ __('repayments.disbursed_col') }}</th>
                <th>{{ __('repayments.amount_paid_col') }}</th>
                <th>{{ __('repayments.outstanding') }}</th>
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
                <td>{{ format_tzs($payment->amount_paid) }}</td>
                <td>{{ format_tzs($payment->outstanding_debt) }}</td>
                <td>
                    <a href="{{ route('repayments.show', $payment) }}" class="app-btn app-btn-secondary text-xs">{{ __('repayments.view_payments') }}</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="app-table-empty">{{ __('repayments.no_records') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="app-card-footer">{{ $payments->links() }}</div>
</div>
</div>
@endsection
