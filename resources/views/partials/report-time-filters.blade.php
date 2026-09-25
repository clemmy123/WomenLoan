@php
    $lang = $langPrefix;
    $periods = $periods ?? \App\Services\ByRegionReportService::PERIODS;
    $showFiscalYearField = $showFiscalYear ?? true;
    $showPeriodField = $showPeriod ?? true;
    $showDatesField = $showDates ?? true;
    $showSortField = $showSort ?? true;
    $fiscalYearOptions = $fiscalYearOptions ?? [];
    $sortOptions = $sortOptions ?? [];
    $selectedPeriod = (string) ($selectedPeriod ?? request('period', 'annually'));
    $selectedFiscalYear = (string) ($selectedFiscalYear ?? '');
    $selectedDateFrom = (string) ($selectedDateFrom ?? '');
    $selectedDateTo = (string) ($selectedDateTo ?? '');
    $selectedSort = (string) ($selectedSort ?? 'newest');
    $datesVisible = $revealTimeFilters ?? false;
    $sortVisible = $revealTimeFilters ?? false;
@endphp

@if($showFiscalYearField)
    <div class="wizard-field">
        <label class="app-label" for="fiscal_year">{{ __($lang.'.fiscal_year') }}</label>
        <div class="app-filter-control has-clear">
            <select name="fiscal_year" id="fiscal_year" class="app-select">
                @foreach($fiscalYearOptions as $fyKey => $fyLabel)
                    <option value="{{ $fyKey }}" @selected($selectedFiscalYear === (string) $fyKey)>{{ $fyLabel }}</option>
                @endforeach
            </select>
            <button type="button" class="app-filter-clear-inside" data-filter-clear="fiscal_year" title="{{ __('common.clear') }}" aria-label="{{ __('common.clear') }}">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
    </div>
@endif

@if($showPeriodField)
    <div class="wizard-field">
        <label class="app-label" for="period">{{ __($lang.'.period') }}</label>
        @include('partials.period-segmented', ['periods' => $periods, 'selectedPeriod' => $selectedPeriod, 'compact' => true])
    </div>
@endif

@if($showDatesField)
    <div class="wizard-field{{ $datesVisible ? '' : ' d-none' }}" data-filter-reveal="dates">
        <label class="app-label" for="date_from">{{ __($lang.'.date_from') }}</label>
        <div class="app-filter-control app-filter-control--input">
            <input type="date" name="date_from" id="date_from" class="app-input" value="{{ $selectedDateFrom }}">
            <button type="button" class="app-filter-clear-inside{{ $selectedDateFrom ? '' : ' d-none' }}" data-filter-clear="date_from" title="{{ __('common.clear') }}" aria-label="{{ __('common.clear') }}">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
    </div>
    <div class="wizard-field{{ $datesVisible ? '' : ' d-none' }}" data-filter-reveal="dates">
        <label class="app-label" for="date_to">{{ __($lang.'.date_to') }}</label>
        <div class="app-filter-control app-filter-control--input">
            <input type="date" name="date_to" id="date_to" class="app-input" value="{{ $selectedDateTo }}">
            <button type="button" class="app-filter-clear-inside{{ $selectedDateTo ? '' : ' d-none' }}" data-filter-clear="date_to" title="{{ __('common.clear') }}" aria-label="{{ __('common.clear') }}">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
            <input type="hidden" name="use_custom_dates" id="use_custom_dates" value="{{ ($useCustomDates ?? '') }}">
        </div>
    </div>
@endif

@if($showSortField)
    <div class="wizard-field{{ $sortVisible ? '' : ' d-none' }}" data-filter-reveal="sort">
        <label class="app-label" for="sort">{{ __($lang.'.sort_by') }}</label>
        <div class="app-filter-control has-clear">
            <select name="sort" id="sort" class="app-select">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedSort === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" class="app-filter-clear-inside" data-filter-clear="sort" title="{{ __('common.clear') }}" aria-label="{{ __('common.clear') }}">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
    </div>
@endif
