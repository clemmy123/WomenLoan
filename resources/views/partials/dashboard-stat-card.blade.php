@props([
    'url',
    'gradient',
    'label',
    'value',
    'ariaLabel',
    'icon',
    'meta' => null,
    'metaClass' => '',
    'wide' => false,
    'valueClass' => '',
    'constant' => false,
    'breakdown' => [],
])

@php
    $cardClass = 'dashboard-stat-card '.($constant ? 'dashboard-stat-card--constant' : 'dashboard-stat-card--interactive').' dashboard-stat-card--tone-'.$gradient.' w-full'.($wide ? ' dashboard-stat-card--wide' : '');
    $hasBreakdown = is_array($breakdown) && count($breakdown) > 0;
@endphp

@if($hasBreakdown)
    <div class="{{ $cardClass }} dashboard-stat-card--split">
        <a href="{{ $url }}" class="dashboard-stat-card-main" aria-label="{{ $ariaLabel }}">
            <span class="dashboard-stat-card-orb" aria-hidden="true"></span>
            <span class="dashboard-stat-card-shine" aria-hidden="true"></span>
            <div class="dashboard-stat-card-body">
                <div class="dashboard-stat-card-head">
                    <p class="dashboard-stat-card-label">{{ $label }}</p>
                    <span class="dashboard-stat-card-icon" aria-hidden="true">
                        {!! $icon !!}
                    </span>
                </div>
                <p class="dashboard-stat-card-value {{ $valueClass }}">{{ $value }}</p>
                @if($meta)
                    <p class="dashboard-stat-card-meta {{ $metaClass }}">{{ $meta }}</p>
                @endif
            </div>
        </a>
        <div class="dashboard-stat-card-breakdown">
            @foreach($breakdown as $item)
                <a href="{{ $item['url'] }}" class="dashboard-stat-card-breakdown-link dashboard-stat-card-breakdown-link--{{ $item['tone'] ?? 'default' }}{{ ($item['active'] ?? false) ? ' is-active' : '' }}">
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['value'] }}</strong>
                </a>
            @endforeach
        </div>
    </div>
@else
    <a
        href="{{ $url }}"
        class="{{ $cardClass }}"
        aria-label="{{ $ariaLabel }}"
    >
        <span class="dashboard-stat-card-orb" aria-hidden="true"></span>
        <span class="dashboard-stat-card-shine" aria-hidden="true"></span>
        <div class="dashboard-stat-card-body">
            <div class="dashboard-stat-card-head">
                <p class="dashboard-stat-card-label">{{ $label }}</p>
                <span class="dashboard-stat-card-icon" aria-hidden="true">
                    {!! $icon !!}
                </span>
            </div>
            <p class="dashboard-stat-card-value {{ $valueClass }}">{{ $value }}</p>
            @if($meta)
                <p class="dashboard-stat-card-meta {{ $metaClass }}">{{ $meta }}</p>
            @endif
        </div>
    </a>
@endif
