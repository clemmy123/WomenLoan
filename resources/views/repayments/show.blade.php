@extends('layouts.app')

@section('title', __('repayments.schedule_title'))

@section('content')
@php
    $paid = (float) $payment->amount_paid;
    $outstanding = (float) $payment->outstanding_debt;
    $disbursed = (float) $payment->amount_disbursed;
    $interest = (float) $payment->interest_amount;
    $totalPayable = $disbursed + $interest;
    $collectable = $paid + $outstanding;
    $collectionRate = $collectable > 0
        ? (int) min(100, round(($paid / $collectable) * 100))
        : 0;
    $statusLabel = $indexService->statusLabel($payment);
@endphp

<div class="page space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('repayments.schedule_title') }}</h1>
            <p class="page-subtitle font-mono text-indigo-600">{{ $payment->loan?->loan_track_id }}</p>
        </div>
        <a href="{{ route('repayments.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.repayments') }}</a>
    </div>

    @if(session('error'))
        @include('partials.status-card', [
            'type' => 'error',
            'message' => session('error'),
            'autoDismiss' => true,
        ])
    @endif

    @include('partials.repayment-summary-strip', [
        'title' => __('repayments.summary_title'),
        'copy' => __('repayments.detail_summary_copy', [
            'name' => $payment->loan?->applicant?->full_name ?? '—',
            'status' => $statusLabel,
            'interest' => format_tzs($interest),
            'total' => format_tzs($totalPayable),
        ]),
        'rate' => $collectionRate,
        'metrics' => [
            [
                'label' => __('repayments.disbursed_col'),
                'value' => format_tzs($disbursed),
            ],
            [
                'label' => __('repayments.amount_paid_col'),
                'value' => format_tzs($paid),
                'tone' => 'paid',
            ],
            [
                'label' => __('repayments.outstanding'),
                'value' => format_tzs($outstanding),
                'tone' => 'outstanding',
            ],
        ],
    ])

    @if($isLoanApplicant)
        @if($inGracePeriod && $graceEndsAt)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30 p-5">
                <p class="font-bold text-amber-900 dark:text-amber-200">{{ __('repayments.grace_active_title') }}</p>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-300/90">
                    {{ __('repayments.grace_active_message', ['date' => $graceEndsAt->translatedFormat('d M Y')]) }}
                </p>
            </div>
        @elseif($outstanding > 0 && $nextInstallment)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/30 p-5">
                <p class="font-bold text-emerald-900 dark:text-emerald-200">{{ __('repayments.start_payment_title') }}</p>
                <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300/90">
                    {{ __('repayments.start_payment_message', [
                        'number' => $nextInstallment['installment'] ?? 1,
                        'amount' => format_tzs($nextInstallment['amount_due'] ?? $suggestedAmount),
                        'date' => \Illuminate\Support\Carbon::parse($nextInstallment['due_date'])->translatedFormat('d M Y'),
                    ]) }}
                </p>
                <p class="mt-3 font-mono text-lg font-bold text-emerald-700 dark:text-emerald-300">
                    {{ __('repayments.payment_number', ['number' => $nextInstallment['installment'] ?? 1]) }}
                </p>
            </div>
        @endif
    @endif

    @if($isLoanApplicant && (float) $payment->outstanding_debt > 0)
    <div class="app-card app-card-padded space-y-4">
        <h2 class="font-bold text-slate-900 dark:text-white">{{ __('repayments.pay_here') }}</h2>
        <div class="rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 p-4">
            <p class="text-sm text-slate-600 dark:text-zinc-400">{{ __('repayments.pay_instruction') }}</p>
            <dl class="mt-3 grid gap-2 sm:grid-cols-3 text-sm">
                <div>
                    <dt class="text-slate-500">{{ __('repayments.bank_name') }}</dt>
                    <dd class="font-semibold">{{ $account['bank_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('repayments.account_number') }}</dt>
                    <dd class="font-mono font-bold text-lg text-indigo-700 dark:text-indigo-300">{{ $account['account_number'] }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('repayments.account_name') }}</dt>
                    <dd class="font-semibold">{{ $account['account_name'] }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">{{ __('repayments.accepted_methods') }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-zinc-400">{{ implode(' · ', $paymentMethods) }}</p>
        </div>

        @if($canRecordPayment)
        <form method="POST" action="{{ route('repayments.pay', $payment) }}" class="grid gap-4 sm:grid-cols-3">
            @csrf
            <div>
                <label class="app-label" for="amount">{{ __('repayments.payment_amount') }}</label>
                @include('partials.inputs.amount-input', [
                    'name' => 'amount',
                    'id' => 'amount',
                    'value' => old('amount', $suggestedAmount ?: ''),
                    'required' => true,
                ])
            </div>
            <div>
                <label class="app-label" for="method">{{ __('repayments.method') }}</label>
                <select name="method" id="method" required class="app-input">
                    <option value="">{{ __('repayments.select_method') }}</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" @selected(old('method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
                @error('method')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-end">
                <button type="submit" class="app-btn app-btn-primary w-full">{{ __('repayments.submit_payment') }}</button>
            </div>
        </form>
        @elseif($inGracePeriod)
        <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('repayments.recording_after_grace') }}</p>
        @endif
    </div>
    @endif

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h2 class="font-bold text-slate-900 dark:text-white">{{ __('repayments.payment_history') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.date') }}</th>
                        <th>{{ __('repayments.payment_amount') }}</th>
                        <th>{{ __('repayments.payment_reference') }}</th>
                        <th>{{ __('repayments.method') }}</th>
                        <th class="print:hidden">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $tx)
                    <tr>
                        <td>{{ format_payment_datetime($tx['date'] ?? null) }}</td>
                        <td>{{ format_tzs($tx['amount']) }}</td>
                        <td class="font-mono">{{ $tx['reference'] ?? $tx['receipt_number'] ?? '—' }}</td>
                        <td>{{ $tx['method'] ?? '—' }}</td>
                        <td class="print:hidden">
                            <a href="{{ route('repayments.receipt', [$payment, $index]) }}" class="app-btn app-btn-secondary app-btn-sm">
                                {{ __('repayments.view_receipt') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="app-table-empty">{{ __('repayments.no_payments_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
