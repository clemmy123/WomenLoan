@if ($paginator->total() > 0)
    <nav class="wdf-pagination wdf-pagination--simple" role="navigation" aria-label="{{ __('pagination.label') }}">
        <div class="wdf-pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="wdf-page-btn wdf-page-btn--disabled" aria-disabled="true">{{ __('pagination.previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="wdf-page-btn" rel="prev">{{ __('pagination.previous') }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="wdf-page-btn" rel="next">{{ __('pagination.next') }}</a>
            @else
                <span class="wdf-page-btn wdf-page-btn--disabled" aria-disabled="true">{{ __('pagination.next') }}</span>
            @endif
        </div>
    </nav>
@endif
