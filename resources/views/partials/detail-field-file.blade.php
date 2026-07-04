@props(['label', 'path' => null])

@php
    $docUrl = filled($path) ? asset('storage/'.$path) : null;
@endphp

<div {{ $attributes->merge(['class' => 'doc-attachment-field']) }}>
    @if(filled($path))
        <button
            type="button"
            class="doc-attachment-card doc-attachment-card--view"
            data-doc-view
            data-doc-url="{{ $docUrl }}"
            data-doc-title="{{ $label }}"
        >
            <span class="doc-attachment-icon" aria-hidden="true">
                @include('partials.icons.document-download')
            </span>
            <span class="doc-attachment-body">
                <span class="doc-attachment-title">{{ $label }}</span>
                <span class="doc-attachment-action">{{ __('common.view_document') }}</span>
            </span>
        </button>
    @else
        <div class="doc-attachment-card doc-attachment-card--empty">
            <span class="doc-attachment-icon doc-attachment-icon--muted" aria-hidden="true">
                @include('partials.icons.document-download')
            </span>
            <span class="doc-attachment-body">
                <span class="doc-attachment-title">{{ $label }}</span>
                <span class="doc-attachment-hint">{{ __('common.no_file') }}</span>
            </span>
        </div>
    @endif
</div>
