@php
    $variant = $variant ?? 'secondary';
    $class = $class ?? '';
@endphp

<span @class(["badge badge-{$variant}", $class])>
    {{ $text ?? '' }}
</span>
