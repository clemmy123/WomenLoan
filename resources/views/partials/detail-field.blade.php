@props(['label', 'value' => null, 'mono' => false])

<div>
    <span class="detail-field-label">{{ $label }}</span>
    <span class="detail-field-value {{ $mono ? 'detail-field-mono' : '' }}">
        {{ filled($value) ? $value : __('common.na') }}
    </span>
</div>
