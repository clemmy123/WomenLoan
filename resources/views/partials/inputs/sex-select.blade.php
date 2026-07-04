@props([
    'name' => 'sex',
    'id' => null,
    'value' => '',
    'required' => false,
    'class' => 'app-select',
])

@php
    $inputId = $id ?? $name;
    $selected = old($name, $value);
@endphp

<select name="{{ $name }}" id="{{ $inputId }}" @required($required) {{ $attributes->merge(['class' => $class]) }}>
    <option value="">{{ __('applicants.select_sex') }}</option>
    <option value="Female" @selected($selected === 'Female')>{{ __('applicants.female') }}</option>
    <option value="Male" @selected($selected === 'Male')>{{ __('applicants.male') }}</option>
</select>
