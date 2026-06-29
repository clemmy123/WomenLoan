@php
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->join('');
    $class = $class ?? 'h-9 w-9 rounded-full ring-2 ring-white dark:ring-dm-700 bg-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0';
@endphp
<div class="{{ $class }}">
    {{ $initials ?: 'U' }}
</div>
