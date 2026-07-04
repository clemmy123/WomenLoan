@props(['size' => 'header'])

@php
    $sizes = [
        'header' => 'app-brand-logo app-brand-logo-header',
        'auth' => 'app-brand-logo app-brand-logo-auth',
    ];
    $class = $sizes[$size] ?? $sizes['header'];
@endphp

<img src="{{ asset('images/nembo2.png') }}" alt="{{ __('nav.welcome') }}" {{ $attributes->merge(['class' => $class]) }} decoding="async">
