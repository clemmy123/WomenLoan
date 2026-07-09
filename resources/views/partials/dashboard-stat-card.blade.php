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
])

<button
    type="button"
    @click="window.location.assign(@js($url))"
    class="dashboard-stat-card {{ $constant ? 'dashboard-stat-card--constant' : 'dashboard-stat-card--interactive' }} dashboard-stat-card--tone-{{ $gradient }} w-full{{ $wide ? ' dashboard-stat-card--wide' : '' }}"
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
</button>
