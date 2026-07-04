@props([
    'name' => 'amount',
    'id' => null,
    'value' => '',
    'required' => false,
    'placeholder' => '0',
    'class' => '',
    'inputAttributes' => '',
])

@php
    use App\Support\IdentityNormalizer;

    $inputId = $id ?? $name.'_display';
    $rawValue = IdentityNormalizer::normalizeAmount(old($name, $value));
    $displayValue = format_amount_input($rawValue);
@endphp

<div {{ $attributes->merge(['class' => 'app-amount-field']) }} data-amount-field>
    <span class="app-amount-prefix" aria-hidden="true">TZS</span>
    <input
        type="text"
        id="{{ $inputId }}"
        value="{{ $displayValue }}"
        inputmode="numeric"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        data-amount-display
        @required($required)
        {!! $inputAttributes !!}
        class="app-amount-display app-input {{ $class }}"
    >
    <input type="hidden" name="{{ $name }}" value="{{ $rawValue }}" data-amount-hidden>
</div>
