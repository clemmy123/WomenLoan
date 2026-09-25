@php
    $periodOptions = $periods ?? [];
    $selectedPeriod = (string) ($selectedPeriod ?? request('period', 'annually'));
    $submitOnChange = $submitOnChange ?? false;
@endphp
<div class="app-segmented{{ ($compact ?? false) ? ' app-segmented-compact' : '' }}" role="radiogroup" aria-label="{{ __('reports.period') }}" data-period-segmented>
    <input type="hidden" name="period" id="period" value="{{ $selectedPeriod }}" data-period-input>
    @foreach($periodOptions as $period)
        <button
            type="button"
            class="app-segmented-btn{{ $selectedPeriod === (string) $period ? ' is-active' : '' }}"
            data-period-value="{{ $period }}"
            @if($submitOnChange) data-period-submit="1" @endif
            role="radio"
            aria-checked="{{ $selectedPeriod === (string) $period ? 'true' : 'false' }}"
        >
            {{ __('reports.period_'.$period) }}
        </button>
    @endforeach
</div>
