@if ($paginator->total() > 0)
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $elements = $elements ?? [];
    @endphp
    <nav class="wdf-pagination" role="navigation" aria-label="{{ __('pagination.label') }}">
        <div class="wdf-pagination-meta">
            <span class="wdf-pagination-meta-label">{{ __('pagination.page') }}</span>
            <label class="wdf-pagination-select-wrap">
                <span class="sr-only">{{ __('pagination.page') }}</span>
                <select
                    class="wdf-pagination-select"
                    onchange="window.location.assign(this.value)"
                    @disabled($last <= 1)
                >
                    @for ($page = 1; $page <= $last; $page++)
                        <option
                            value="{{ $paginator->url($page) }}"
                            @selected($page === $current)
                        >{{ $page }}</option>
                    @endfor
                </select>
            </label>
            <span class="wdf-pagination-meta-label">{{ __('pagination.of', ['last' => $last]) }}</span>
        </div>

        <div class="wdf-pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="wdf-page-btn wdf-page-btn--disabled" aria-disabled="true">
                    {{ __('pagination.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="wdf-page-btn" rel="prev">
                    {{ __('pagination.previous') }}
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="wdf-page-btn wdf-page-btn--ellipsis" aria-hidden="true">&hellip;</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $current)
                            <span class="wdf-page-btn wdf-page-btn--active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="wdf-page-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="wdf-page-btn" rel="next">
                    {{ __('pagination.next') }}
                </a>
            @else
                <span class="wdf-page-btn wdf-page-btn--disabled" aria-disabled="true">
                    {{ __('pagination.next') }}
                </span>
            @endif
        </div>
    </nav>
@endif
