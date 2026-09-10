@props([
    'name' => 'nin',
    'id' => null,
    'value' => '',
    'readonly' => false,
    'required' => false,
    'class' => '',
    'placeholder' => '19000000-00000-00000-02',
    'incompleteMessage' => null,
])

@php
    use App\Support\IdentityNormalizer;

    $inputId = $id ?? $name;
    $formatted = IdentityNormalizer::formatNin(old($name, $value));
    $incompleteMessage = $incompleteMessage ?? __('common.nin_incomplete');
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
        maxlength="23"
        data-nin-input
        data-incomplete-message="{{ $incompleteMessage }}"
        @readonly($readonly)
        @required($required && ! $readonly)
        {{ $attributes->merge(['class' => trim('app-nin-input '.$class)]) }}
    >
    <p class="app-identity-error" data-identity-error hidden role="alert"></p>
</div>
