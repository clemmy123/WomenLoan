@php
    $periodOptions = $periods ?? [];
    $periodChange = $periodChange ?? 'onPeriodChange()';
    $compact = $compact ?? false;
@endphp
<div class="app-segmented{{ $compact ? ' app-segmented-compact' : '' }}" role="radiogroup" aria-label="{{ __('reports.period') }}">
    <input type="hidden" name="period" id="period" x-model="selectedPeriod">
    @foreach($periodOptions as $period)
        <button
            type="button"
            class="app-segmented-btn"
            :class="{ 'is-active': selectedPeriod === '{{ $period }}' }"
            @click="selectedPeriod = '{{ $period }}'; {{ $periodChange }}"
            role="radio"
            :aria-checked="selectedPeriod === '{{ $period }}' ? 'true' : 'false'"
        >
            {{ __('reports.period_'.$period) }}
        </button>
    @endforeach
</div>
