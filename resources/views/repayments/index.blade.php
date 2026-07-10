@extends('layouts.app')

@section('title', __('repayments.payments_title'))

@section('content')
@php
    $collectable = (float) $summary['total_paid'] + (float) $summary['total_outstanding'];
    $collectionRate = $collectable > 0
        ? (int) min(100, round(((float) $summary['total_paid'] / $collectable) * 100))
        : 0;
@endphp

<div class="page space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('repayments.payments_title') }}</h1>
            <p class="page-subtitle">{{ __('repayments.index_subtitle') }}</p>
        </div>
        <div class="page-actions flex flex-wrap gap-2">
            @include('partials.report-export-buttons', [
                'excelRoute' => route('repayments.export.excel', request()->query()),
                'pdfRoute' => route('repayments.export.pdf', request()->query()),
                'excelLabel' => __('repayments.export_excel'),
                'pdfLabel' => __('repayments.export_pdf'),
            ])
        </div>
    </div>

    @include('partials.repayment-summary-strip', [
        'title' => __('repayments.summary_title'),
        'copy' => __('repayments.summary_copy', [
            'loans' => number_format($summary['count']),
            'active' => number_format($summary['active_count']),
            'cleared' => number_format($summary['cleared_count']),
        ]),
        'rate' => $collectionRate,
        'metrics' => [
            [
                'label' => __('repayments.disbursed_col'),
                'value' => format_tzs($summary['total_disbursed']),
            ],
            [
                'label' => __('repayments.amount_paid_col'),
                'value' => format_tzs($summary['total_paid']),
                'tone' => 'paid',
            ],
            [
                'label' => __('repayments.outstanding'),
                'value' => format_tzs($summary['total_outstanding']),
                'tone' => 'outstanding',
            ],
        ],
    ])

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h2 class="font-bold text-slate-900 dark:text-white">{{ __('repayments.list_title') }}</h2>
        </div>

        @include('partials.loan-list-toolbar', [
            'action' => route('repayments.index'),
            'search' => $search,
            'status' => $status,
            'statusOptions' => $statusOptions,
            'sort' => $sort,
            'sortOptions' => $sortOptions,
            'searchPlaceholder' => __('repayments.search_placeholder'),
            'showClear' => $search !== '' || $status !== 'all' || $sort !== 'newest',
            'clearUrl' => route('repayments.index'),
        ])

        @if($payments->total())
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('dashboard.track_id') }}</th>
                            <th>{{ __('loans.applicant_name') }}</th>
                            <th>{{ __('repayments.disbursed_col') }}</th>
                            <th>{{ __('repayments.amount_paid_col') }}</th>
                            <th>{{ __('repayments.outstanding') }}</th>
                            <th>{{ __('dashboard.status') }}</th>
                            <th class="w-28">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>
                                    @include('partials.track-id-chip', ['trackId' => $payment->loan?->loan_track_id ?? '—'])
                                </td>
                                <td>{{ $payment->loan?->applicant?->full_name ?? '—' }}</td>
                                <td>{{ format_tzs($payment->amount_disbursed) }}</td>
                                <td>{{ format_tzs($payment->amount_paid) }}</td>
                                <td class="font-semibold text-amber-600 dark:text-amber-400">{{ format_tzs($payment->outstanding_debt) }}</td>
                                <td>
                                    @include('partials.badge', [
                                        'variant' => $indexService->statusVariant($payment),
                                        'text' => $indexService->statusLabel($payment),
                                    ])
                                </td>
                                <td>
                                    <div class="app-row-actions">
                                        <a
                                            href="{{ route('repayments.show', $payment) }}"
                                            class="app-icon-btn app-icon-btn--view"
                                            title="{{ __('repayments.view_payments') }}"
                                            aria-label="{{ __('repayments.view_payments') }}"
                                        >
                                            @include('partials.icons.eye')
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="app-card-footer">{{ $payments->links() }}</div>
        @else
            <p class="app-table-empty">
                @if($search !== '' || $status !== 'all')
                    {{ __('repayments.no_search_results') }}
                @else
                    {{ __('repayments.no_records') }}
                @endif
            </p>
        @endif
    </div>
</div>
@endsection
