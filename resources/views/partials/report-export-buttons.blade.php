@props([
    'excelRoute',
    'pdfRoute',
    'excelLabel' => __('reports.export_excel'),
    'pdfLabel' => __('reports.export_pdf'),
])

<a href="{{ $excelRoute }}" class="app-btn app-btn-secondary app-export-btn text-sm">
    <svg class="app-export-icon app-export-icon--excel" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <rect x="3" y="2" width="18" height="20" rx="2" fill="currentColor" opacity="0.15"/>
        <path d="M8 7l3 4-3 4M13 15h3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
        <rect x="3" y="2" width="18" height="20" rx="2" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    <span>{{ $excelLabel }}</span>
</a>
<a href="{{ $pdfRoute }}" class="app-btn app-btn-secondary app-export-btn text-sm">
    <svg class="app-export-icon app-export-icon--pdf" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <rect x="4" y="2" width="16" height="20" rx="2" fill="currentColor" opacity="0.15"/>
        <path d="M8 7h8M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        <rect x="4" y="2" width="16" height="20" rx="2" stroke="currentColor" stroke-width="1.5"/>
        <path d="M7 18h10" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
    </svg>
    <span>{{ $pdfLabel }}</span>
</a>
