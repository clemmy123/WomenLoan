@props([
    'name' => 'tin_number',
    'id' => null,
    'value' => '',
    'readonly' => false,
    'required' => false,
    'class' => '',
    'placeholder' => '111-111-111',
    'incompleteMessage' => null,
])

@php
    use App\Support\IdentityNormalizer;

    $inputId = $id ?? $name;
    $formatted = IdentityNormalizer::formatTin(old($name, $value));
    $incompleteMessage = $incompleteMessage ?? __('common.tin_incomplete');
@endphp

<div class="app-identity-field" data-identity-field>
    <input
        type="text"
        id="{{ $inputId }}"
        name="{{ $name }}"
        value="{{ $formatted }}"
        inputmode="numeric"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        maxlength="11"
        pattern="\d{3}-\d{3}-\d{3}"
        data-tin-input
        data-incomplete-message="{{ $incompleteMessage }}"
        @readonly($readonly)
        @required($required && ! $readonly)
        {{ $attributes->merge(['class' => trim('app-tin-input '.$class)]) }}
    >
    <p class="app-identity-error" data-identity-error hidden role="alert"></p>
</div>
