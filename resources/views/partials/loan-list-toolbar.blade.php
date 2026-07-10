@props([
    'action',
    'search' => '',
    'sort' => 'newest',
    'sortOptions' => [],
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
    $searchPlaceholder = $searchPlaceholder ?? __('dashboard.recent_search_placeholder');
@endphp

<form
    method="GET"
    action="{{ $action }}"
    class="dashboard-recent-toolbar"
    x-ref="recentForm"
>
    @foreach($hiddenFields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <div class="dashboard-recent-toolbar-row">
        <input
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="{{ $searchPlaceholder }}"
            class="dashboard-recent-input"
            autocomplete="off"
            @input.debounce.350ms="$refs.recentForm.requestSubmit()"
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
                <span class="dashboard-recent-sort-label">{{ __('dashboard.sort_by') }}</span>
                <select name="sort" class="dashboard-recent-select" @change="$refs.recentForm.requestSubmit()">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        @if($showClear && $clearUrl)
            <a href="{{ $clearUrl }}" class="dashboard-recent-clear">{{ __('common.clear') }}</a>
        @endif
    </div>
</form>
