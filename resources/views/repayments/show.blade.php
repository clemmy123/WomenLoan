@extends('layouts.app')

@section('title', __('repayments.schedule_title'))

@section('content')
<div class="page space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('repayments.schedule_title') }}</h1>
            <p class="page-subtitle font-mono text-indigo-600">{{ $payment->loan?->loan_track_id }}</p>
        </div>
        <a href="{{ route('repayments.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← {{ __('nav.repayments') }}</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <p class="text-xs text-slate-500 uppercase font-semibold">{{ __('repayments.disbursed_col') }}</p>
            <p class="text-xl font-bold mt-1">{{ format_tzs($payment->amount_disbursed) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <p class="text-xs text-slate-500 uppercase font-semibold">{{ __('repayments.interest') }}</p>
            <p class="text-xl font-bold mt-1">{{ format_tzs($payment->interest_amount) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <p class="text-xs text-slate-500 uppercase font-semibold">{{ __('repayments.total_payable') }}</p>
            <p class="text-xl font-bold text-indigo-600 mt-1">{{ format_tzs((float) $payment->amount_disbursed + (float) $payment->interest_amount) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-5">
            <p class="text-xs text-slate-500 uppercase font-semibold">{{ __('repayments.outstanding') }}</p>
            <p class="text-xl font-bold text-amber-600 mt-1">{{ format_tzs($payment->outstanding_debt) }}</p>
        </div>
    </div>

    @if((float) $payment->outstanding_debt > 0)
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
        </div>

        @can('record repayment')
        <form method="POST" action="{{ route('repayments.pay', $payment) }}" class="grid gap-4 sm:grid-cols-3">
            @csrf
            <div>
                <label class="app-label" for="amount">{{ __('repayments.payment_amount') }}</label>
                <input type="number" name="amount" id="amount" min="1" step="1" required
                    value="{{ old('amount', $installments[0]['amount_due'] ?? '') }}"
                    class="app-input">
            </div>
            <div>
                <label class="app-label" for="reference">{{ __('repayments.payment_reference') }}</label>
                <input type="text" name="reference" id="reference" value="{{ old('reference') }}" class="app-input" placeholder="{{ __('repayments.reference_placeholder') }}">
            </div>
            <div class="flex items-end">
                <button type="submit" class="app-btn app-btn-primary w-full">{{ __('repayments.submit_payment') }}</button>
            </div>
        </form>
        @endcan
    </div>
    @endif

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h2 class="font-bold text-slate-900 dark:text-white">{{ __('repayments.monthly_schedule') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('repayments.due_date') }}</th>
                        <th>{{ __('repayments.amount_due') }}</th>
                        <th>{{ __('repayments.amount_paid_col') }}</th>
                        <th>{{ __('common.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($installments as $row)
                    <tr>
                        <td>{{ $row['installment'] }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row['due_date'])->translatedFormat('d M Y') }}</td>
                        <td>{{ format_tzs($row['amount_due']) }}</td>
                        <td>{{ format_tzs($row['amount_paid'] ?? 0) }}</td>
                        <td>
                            <span class="app-badge {{ ($row['status'] ?? 'pending') === 'paid' ? 'app-badge-success' : 'app-badge-warning' }}">
                                {{ __('repayments.status.' . ($row['status'] ?? 'pending')) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="app-table-empty">{{ __('repayments.no_schedule') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(count($transactions))
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
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($tx['date'])->translatedFormat('d M Y') }}</td>
                        <td>{{ format_tzs($tx['amount']) }}</td>
                        <td>{{ $tx['reference'] ?? '—' }}</td>
                        <td>{{ $tx['method'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
