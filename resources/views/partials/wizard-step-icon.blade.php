@php
    $icon = $icon ?? 'document';
@endphp
@switch($icon)
    @case('business')
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 9.5 12 4l9 5.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
            <path d="M9 21V12h6v9" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
        @break
    @case('guarantor')
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.75"/>
            <path d="M5 19c0-3.3 3.1-5 7-5s7 1.7 7 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
        @break
    @case('amount')
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.75"/>
            <circle cx="12" cy="12" r="2.25" stroke="currentColor" stroke-width="1.75"/>
            <path d="M7 10h.01M17 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        @break
    @case('bank')
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.75"/>
            <path d="M3 10h18" stroke="currentColor" stroke-width="1.75"/>
            <path d="M7 15h2M15 15h2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
        @break
    @case('declaration')
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8 4h8l1 2h3v14H4V6h3l1-2Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
            <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        @break
    @case('review')
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.75"/>
            <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
        @break
    @default
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8 4h8l1 2h3v14H4V6h3l1-2Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
            <path d="M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
@endswitch
