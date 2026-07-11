@props([
    'title',
    'showLabel',
    'hideLabel',
])

<button
    type="button"
    @click="filtersOpen = !filtersOpen"
    class="list-filters-toggle"
    :aria-expanded="filtersOpen.toString()"
    :aria-label="filtersOpen ? @js($hideLabel) : @js($showLabel)"
>
    <span class="list-filters-toggle-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 5h16l-6 7v5l-4 2v-7L4 5z"/>
        </svg>
    </span>
    <span class="list-filters-toggle-title">{{ $title }}</span>
    <span
        class="list-filters-toggle-state"
        x-text="filtersOpen ? @js($hideLabel) : @js($showLabel)"
    ></span>
</button>
