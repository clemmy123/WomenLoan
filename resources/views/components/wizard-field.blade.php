@props(['label', 'for' => null, 'required' => false])

<div {{ $attributes->merge(['class' => 'wizard-field']) }}>
    <label @if($for) for="{{ $for }}" @endif class="app-label">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    {{ $slot }}
</div>
