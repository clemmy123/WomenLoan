@props([
    'name' => 'phone',
    'id' => null,
    'value' => '',
    'readonly' => false,
    'required' => false,
    'class' => '',
])

@php
    use App\Support\IdentityNormalizer;

    $inputId = $id ?? $name.'_local';
    $hiddenName = $name;
    $localValue = IdentityNormalizer::phoneLocalPart(old($name, $value));
    $normalizedValue = IdentityNormalizer::normalizePhone(old($name, $value));
@endphp

<div {{ $attributes->merge(['class' => 'app-phone-field']) }} data-phone-field>
        <span class="app-phone-prefix" aria-hidden="true">
            <span class="app-phone-flag">@include('partials.flags.tanzania')</span>
            <span class="app-phone-code">+255</span>
        </span>
    <input
        type="tel"
        id="{{ $inputId }}"
        value="{{ $localValue }}"
        inputmode="numeric"
        autocomplete="tel-national"
        maxlength="9"
        placeholder="712345678"
        data-phone-local
        @readonly($readonly)
        @required($required && ! $readonly)
        class="app-phone-local {{ $class }}"
    >
    <input type="hidden" name="{{ $hiddenName }}" value="{{ $normalizedValue }}" data-phone-hidden @required($required && $readonly)>
</div>
