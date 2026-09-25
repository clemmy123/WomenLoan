@props([
    'title',
    'copy',
    'rate',
    'metrics' => [],
    'rateLabel' => null,
    'breakdown' => [],
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

    @if(is_array($breakdown) && count($breakdown))
        <div class="repayment-summary-strip-breakdown">
            @foreach($breakdown as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="repayment-summary-strip-breakdown-link repayment-summary-strip-breakdown-link--{{ $item['tone'] ?? 'all' }}{{ ($item['active'] ?? false) ? ' is-active' : '' }}"
                >
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['value'] }}</strong>
                    @if(! empty($item['meta']))
                        <em>{{ $item['meta'] }}</em>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

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
