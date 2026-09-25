@props(['label', 'for' => null, 'required' => false, 'errorKey' => null, 'requiredMessage' => null])

@php
    $fieldErrorKey = $errorKey ?? $for;
    $emptyMessage = $requiredMessage
        ?? ($fieldErrorKey ? __('validation.custom.'.$fieldErrorKey.'.required') : null);
@endphp

<div
    {{ $attributes->merge(['class' => 'wizard-field']) }}
    @if($required && is_string($emptyMessage) && $emptyMessage !== '')
        data-required-message="{{ $emptyMessage }}"
    @endif
>
    <label @if($for) for="{{ $for }}" @endif class="app-label">
        {{ $label }}
        @if($required) @include('partials.required-mark') @endif
    </label>
    {{ $slot }}
    @if($fieldErrorKey)
        @error($fieldErrorKey)
            <p class="wizard-field-error" data-wizard-field-error data-server-error="true">{{ $message }}</p>
        @else
            <p class="wizard-field-error" data-wizard-field-error hidden></p>
        @enderror
    @endif
</div>
