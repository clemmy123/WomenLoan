@php
    $targetId = $targetId ?? 'password';
    $variant = $variant ?? 'app';
@endphp
<div
    class="password-requirements password-requirements--{{ $variant }}"
    data-password-requirements
    data-password-target="{{ $targetId }}"
    aria-live="polite"
>
    <p class="password-requirements-title">{{ __('auth.password_requirements_title') }}</p>
    <ul class="password-requirements-list">
        <li data-rule="length">{{ __('auth.password_rule_length') }}</li>
        <li data-rule="letter">{{ __('auth.password_rule_letter') }}</li>
        <li data-rule="uppercase">{{ __('auth.password_rule_uppercase') }}</li>
        <li data-rule="number">{{ __('auth.password_rule_number') }}</li>
        <li data-rule="symbol">{{ __('auth.password_rule_symbol') }}</li>
    </ul>
    <p class="password-requirements-strong" data-strong-label hidden>{{ __('auth.password_strong') }}</p>
</div>
