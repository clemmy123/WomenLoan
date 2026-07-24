@php
    $lang = $langPrefix;
    $periods = $periods ?? \App\Services\ByRegionReportService::PERIODS;
    $showFiscalYearField = $showFiscalYear ?? true;
    $showPeriodField = $showPeriod ?? true;
    $showDatesField = $showDates ?? true;
    $showSortField = $showSort ?? true;
    $fiscalYearOptions = $fiscalYearOptions ?? [];
    $sortOptions = $sortOptions ?? [];
@endphp

@if($showFiscalYearField)
    <div class="wizard-field" x-show="showFiscalYear" x-cloak>
        <label class="app-label" for="fiscal_year">{{ __($lang.'.fiscal_year') }}</label>
        <div class="app-filter-control has-clear">
            <select
                name="fiscal_year"
                id="fiscal_year"
                class="app-select"
                x-model="selectedFiscalYear"
                @change="onFiscalYearChange()"
            >
                @foreach($fiscalYearOptions as $fyKey => $fyLabel)
                    <option value="{{ $fyKey }}">{{ $fyLabel }}</option>
                @endforeach
            </select>
            <button
                type="button"
                class="app-filter-clear-inside"
                @click.prevent="clearFiscalYearValue()"
                title="{{ __('common.clear') }}"
                aria-label="{{ __('common.clear') }}"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
    </div>
@endif

@if($showPeriodField)
    <div class="wizard-field" x-show="showPeriod" x-cloak>
        <label class="app-label" for="period">{{ __($lang.'.period') }}</label>
        <div class="app-filter-control has-clear">
            <select
                name="period"
                id="period"
                class="app-select"
                x-model="selectedPeriod"
                @change="onPeriodChange()"
            >
                @foreach($periods as $period)
                    <option value="{{ $period }}">{{ __('reports.period_'.$period) }}</option>
                @endforeach
            </select>
            <button
                type="button"
                class="app-filter-clear-inside"
                @click.prevent="clearPeriodValue()"
                title="{{ __('common.clear') }}"
                aria-label="{{ __('common.clear') }}"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
    </div>
@endif

@if($showDatesField)
    <div class="wizard-field" x-show="showDates" x-cloak>
        <label class="app-label" for="date_from">{{ __($lang.'.date_from') }}</label>
        <div class="app-filter-control app-filter-control--input" :class="{ 'has-clear': selectedDateFrom }">
            <input
                type="date"
                name="date_from"
                id="date_from"
                class="app-input"
                x-model="selectedDateFrom"
                @change="onDateChange()"
            >
            <button
                type="button"
                class="app-filter-clear-inside"
                x-show="selectedDateFrom"
                x-cloak
                @click.prevent="clearDateFrom()"
                title="{{ __('common.clear') }}"
                aria-label="{{ __('common.clear') }}"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
    </div>
    <div class="wizard-field" x-show="showDates" x-cloak>
        <label class="app-label" for="date_to">{{ __($lang.'.date_to') }}</label>
        <div class="app-filter-control app-filter-control--input" :class="{ 'has-clear': selectedDateTo }">
            <input
                type="date"
                name="date_to"
                id="date_to"
                class="app-input"
                x-model="selectedDateTo"
                @change="onDateChange()"
            >
            <button
                type="button"
                class="app-filter-clear-inside"
                x-show="selectedDateTo"
                x-cloak
                @click.prevent="clearDateTo()"
                title="{{ __('common.clear') }}"
                aria-label="{{ __('common.clear') }}"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
            <input type="hidden" name="use_custom_dates" id="use_custom_dates" :value="useCustomDates">
        </div>
    </div>
@endif

@if($showSortField)
    <div class="wizard-field" x-show="showSort" x-cloak>
        <label class="app-label" for="sort">{{ __($lang.'.sort_by') }}</label>
        <div class="app-filter-control has-clear">
            <select name="sort" id="sort" class="app-select" x-model="selectedSort">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <button
                type="button"
                class="app-filter-clear-inside"
                @click.prevent="clearSortValue()"
                title="{{ __('common.clear') }}"
                aria-label="{{ __('common.clear') }}"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
    </div>
@endif
