@extends('layouts.app')

@section('title', __('repayments.receipt_title'))

@section('content')
@php
    $applicant = $payment->loan?->applicant;
@endphp
<div class="page page-medium space-y-6">
    <div class="page-header print:hidden">
        <div>
            <h1 class="page-title">{{ __('repayments.receipt_title') }}</h1>
            <p class="page-subtitle font-mono text-indigo-600">{{ $receiptNumber }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="app-btn app-btn-primary">{{ __('repayments.print_receipt') }}</button>
            <a href="{{ route('repayments.show', $payment) }}" class="app-btn app-btn-secondary">{{ __('repayments.back_to_payments') }}</a>
        </div>
    </div>

    @if(session('success'))
        @include('partials.status-card', [
            'type' => 'success',
            'message' => session('success'),
            'class' => 'print:hidden',
            'autoDismiss' => true,
        ])
    @endif

    <div class="payment-receipt">
        <div class="payment-receipt-frame">
            <div class="payment-receipt-inner">
                <div class="payment-receipt-header">
                    <img src="{{ asset('images/nembo2.png') }}" alt="{{ __('nav.welcome') }}" class="payment-receipt-logo">
                    <div>
                        <p class="payment-receipt-org">{{ __('nav.welcome') }}</p>
                        <p class="payment-receipt-subtitle">{{ __('repayments.receipt_title') }}</p>
                    </div>
                </div>

                <div class="payment-receipt-meta">
                    <div>
                        <span class="payment-receipt-label">{{ __('repayments.payment_date') }}</span>
                        <span class="payment-receipt-value payment-receipt-date">{{ format_payment_datetime($tx['date'] ?? null) }}</span>
                    </div>
                    <div>
                        <span class="payment-receipt-label">{{ __('repayments.receipt_number') }}</span>
                        <span class="payment-receipt-value font-mono">{{ $receiptNumber }}</span>
                    </div>
                </div>

                <div class="payment-receipt-divider"></div>

                <div class="payment-receipt-grid">
                    @include('partials.detail-field', ['label' => __('dashboard.track_id'), 'value' => $payment->loan?->loan_track_id, 'mono' => true])
                    @include('partials.detail-field', ['label' => __('loans.applicant_name'), 'value' => $applicant?->full_name])
                    @include('partials.detail-field', ['label' => __('repayments.payment_amount'), 'value' => format_tzs($tx['amount'])])
                    @include('partials.detail-field', ['label' => __('repayments.payment_reference'), 'value' => $tx['reference'] ?? '—'])
                    @include('partials.detail-field', ['label' => __('repayments.method'), 'value' => $tx['method'] ?? '—'])
                    @if(isset($tx['outstanding_after']))
                        @include('partials.detail-field', ['label' => __('repayments.outstanding_after'), 'value' => format_tzs($tx['outstanding_after'])])
                    @endif
                </div>

                <div class="payment-receipt-amount">
                    <p class="payment-receipt-amount-label">{{ __('repayments.amount_received') }}</p>
                    <p class="app-gradient-confirm-amount">{{ format_tzs($tx['amount']) }}</p>
                </div>

                <div class="payment-receipt-qr">
                    <img
                        src="{{ $qrCodeDataUri }}"
                        alt="{{ __('repayments.qr_alt') }}"
                        class="payment-receipt-qr-image"
                        width="160"
                        height="160"
                    >
                    <p class="payment-receipt-qr-caption">{{ __('repayments.qr_caption') }}</p>
                </div>

                <p class="payment-receipt-footer">{{ __('repayments.receipt_footer') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
