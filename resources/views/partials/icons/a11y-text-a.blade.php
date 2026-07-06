@php
    $sizeClass = match ($size ?? 'md') {
        'sm' => 'app-a11y-text-icon--sm',
        'lg' => 'app-a11y-text-icon--lg',
        default => 'app-a11y-text-icon--md',
    };
@endphp
<span class="app-a11y-text-icon {{ $sizeClass }}" aria-hidden="true">A</span>
