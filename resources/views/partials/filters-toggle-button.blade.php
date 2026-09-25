@props([
    'title',
    'showLabel',
    'hideLabel',
    'target' => 'filters-panel',
    'expanded' => false,
])

<button
    type="button"
    class="list-filters-toggle"
    data-bs-toggle="collapse"
    data-bs-target="#{{ $target }}"
    aria-expanded="{{ $expanded ? 'true' : 'false' }}"
    aria-controls="{{ $target }}"
    aria-label="{{ $expanded ? $hideLabel : $showLabel }}"
    data-show-label="{{ $showLabel }}"
    data-hide-label="{{ $hideLabel }}"
>
    <span class="list-filters-toggle-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 5h16l-6 7v5l-4 2v-7L4 5z"/>
        </svg>
    </span>
    <span class="list-filters-toggle-title">{{ $title }}</span>
    <span class="list-filters-toggle-state">{{ $expanded ? $hideLabel : $showLabel }}</span>
</button>
