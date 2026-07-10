@props([
    'title',
    'copy',
    'rate',
    'metrics' => [],
    'rateLabel' => null,
])

@php
    $rateText = $rateLabel ?? __('repayments.collection_rate', ['rate' => $rate]);
@endphp

<section class="repayment-summary-strip" aria-label="{{ $title }}">
    <div class="repayment-summary-strip-top">
        <div>
            <p class="repayment-summary-strip-label">{{ $title }}</p>
            <p class="repayment-summary-strip-copy">{{ $copy }}</p>
        </div>
        <p class="repayment-summary-strip-rate">
            {{ $rateText }}
        </p>
    </div>

    <dl class="repayment-summary-strip-metrics">
        @foreach($metrics as $metric)
            <div>
                <dt>{{ $metric['label'] }}</dt>
                <dd @class([
                    'is-paid' => ($metric['tone'] ?? null) === 'paid',
                    'is-outstanding' => ($metric['tone'] ?? null) === 'outstanding',
                ])>{{ $metric['value'] }}</dd>
            </div>
        @endforeach
    </dl>

    <div
        class="repayment-summary-progress"
        role="progressbar"
        aria-valuenow="{{ $rate }}"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label="{{ $rateText }}"
    >
        <span class="repayment-summary-progress-bar" style="width: {{ $rate }}%"></span>
    </div>
</section>
