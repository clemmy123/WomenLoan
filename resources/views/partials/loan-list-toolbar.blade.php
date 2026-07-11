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
@endphp

<form
    method="GET"
    action="{{ $action }}"
    class="dashboard-recent-toolbar list-filters-toolbar"
    x-data="{ filtersOpen: {{ $filtersOpenByDefault ? 'true' : 'false' }} }"
    x-ref="recentForm"
>
    @foreach($hiddenFields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    @if($hasExtraFilters)
        @include('partials.filters-toggle-button', [
            'title' => __('common.filter'),
            'showLabel' => __('common.show_filters'),
            'hideLabel' => __('common.hide_filters'),
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
            @input.debounce.350ms="$refs.recentForm.requestSubmit()"
        >

        @if($hasExtraFilters)
            <div
                class="dashboard-recent-toolbar-row"
                x-show="filtersOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
            >
                @if($useStatusFilter)
                    <label class="dashboard-recent-sort-wrap">
                        <span class="dashboard-recent-sort-label">{{ __('dashboard.status') }}</span>
                        <select name="status" class="dashboard-recent-select" @change="$refs.recentForm.requestSubmit()">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected((string) $status === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if($useSortFilter)
                    <label class="dashboard-recent-sort-wrap">
                        <span class="dashboard-recent-sort-label">{{ $sortLabel }}</span>
                        <select name="{{ $sortName }}" class="dashboard-recent-select" @change="$refs.recentForm.requestSubmit()">
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
