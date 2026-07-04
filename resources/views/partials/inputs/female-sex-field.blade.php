@props([
    'name' => 'sex',
    'id' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<input type="hidden" name="{{ $name }}" id="{{ $inputId }}" value="Female">
<input type="text" value="{{ __('applicants.female') }}" readonly
    {{ $attributes->merge(['class' => 'app-input bg-gray-100 border-gray-200 text-gray-600 cursor-not-allowed']) }}>
