@props(['label', 'for' => null, 'required' => false])

<div {{ $attributes->merge(['class' => 'wizard-field']) }}>
    <label @if($for) for="{{ $for }}" @endif class="app-label">
        {{ $label }}
        @if($required) @include('partials.required-mark') @endif
    </label>
    {{ $slot }}
</div>
