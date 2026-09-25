@props([
    'action',
    'search' => '',
    'sort' => 'newest',
    'sortOptions' => [],
    'sortLabel' => null,
    'sortName' => 'sort',
    'status' => '',
    'statusOptions' => [],
    'hiddenFields' => [],
    'clearUrl' => null,
    'showClear' => false,
    'searchPlaceholder' => null,
])

@php
    $useStatusFilter = ! empty($statusOptions);
    $useSortFilter = ! empty($sortOptions);
    $hasExtraFilters = $useStatusFilter || $useSortFilter;
    $searchPlaceholder = $searchPlaceholder ?? __('dashboard.recent_search_placeholder');
    $sortLabel = $sortLabel ?? __('dashboard.sort_by');
    $filtersOpenByDefault = $showClear || filled($status) || (filled($sort) && $sort !== 'newest' && $sort !== '');
    $panelId = $panelId ?? 'list-filters-panel';
@endphp

<form method="GET" action="{{ $action }}" class="dashboard-recent-toolbar list-filters-toolbar">
    @foreach($hiddenFields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    @if($hasExtraFilters)
        @include('partials.filters-toggle-button', [
            'title' => __('common.filter'),
            'showLabel' => __('common.show_filters'),
            'hideLabel' => __('common.hide_filters'),
            'target' => $panelId,
            'expanded' => $filtersOpenByDefault,
        ])
    @endif

    <div class="dashboard-recent-toolbar-row list-filters-toolbar-controls">
        <input
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="{{ $searchPlaceholder }}"
            class="dashboard-recent-input"
            autocomplete="off"
            data-auto-submit-form
            data-auto-submit-delay="350"
        >

        @if($hasExtraFilters)
            <div id="{{ $panelId }}" class="collapse dashboard-recent-toolbar-row{{ $filtersOpenByDefault ? ' show' : '' }}">
                @if($useStatusFilter)
                    <label class="dashboard-recent-sort-wrap">
                        <span class="dashboard-recent-sort-label">{{ __('dashboard.status') }}</span>
                        <select name="status" class="dashboard-recent-select" data-auto-submit-form>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected((string) $status === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if($useSortFilter)
                    <label class="dashboard-recent-sort-wrap">
                        <span class="dashboard-recent-sort-label">{{ $sortLabel }}</span>
                        <select name="{{ $sortName }}" class="dashboard-recent-select" data-auto-submit-form>
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}" @selected((string) $sort === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if($showClear && $clearUrl)
                    <a href="{{ $clearUrl }}" class="dashboard-recent-clear">{{ __('common.clear') }}</a>
                @endif
            </div>
        @elseif($showClear && $clearUrl)
            <a href="{{ $clearUrl }}" class="dashboard-recent-clear">{{ __('common.clear') }}</a>
        @endif
    </div>
</form>
