@props([
    'name' => 'nin',
    'id' => null,
    'value' => '',
    'readonly' => false,
    'class' => '',
    'placeholder' => '19000000-00000-00000-00',
])

@php
    use App\Support\IdentityNormalizer;

    $inputId = $id ?? $name;
    $formatted = IdentityNormalizer::formatNin(old($name, $value));
@endphp

<input
    type="text"
    id="{{ $inputId }}"
    name="{{ $name }}"
    value="{{ $formatted }}"
    inputmode="numeric"
    autocomplete="off"
    placeholder="{{ $placeholder }}"
    maxlength="23"
    data-nin-input
    @readonly($readonly)
    {{ $attributes->merge(['class' => trim('app-nin-input '.$class)]) }}
>
