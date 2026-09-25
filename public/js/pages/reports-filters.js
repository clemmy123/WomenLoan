import { captureScrollPosition, restoreScrollPosition } from '../preserve-scroll.js';

function parseConfig(form) {
    try {
        return JSON.parse(form.getAttribute('data-report-filters') || '{}');
    } catch (e) {
        return {};
    }
}

function fillSelect(select, items, selectedId, placeholder) {
    if (! select) {
        return;
    }

    const keepPlaceholder = placeholder ?? select.querySelector('option[value=""]')?.textContent ?? '';
    select.innerHTML = '';

    if (keepPlaceholder !== null) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = keepPlaceholder;
        select.appendChild(option);
    }

    (items || []).forEach((item) => {
        const option = document.createElement('option');
        option.value = String(item.id);
        option.textContent = item.name;
        if (selectedId && String(item.id) === String(selectedId)) {
            option.selected = true;
        }
        select.appendChild(option);
    });

    window.AppSelect?.refreshAppSelect(select);
}

function setHidden(el, hidden) {
    if (! el) {
        return;
    }
    el.classList.toggle('d-none', hidden);
    el.hidden = hidden;
}

function bindReportForm(form) {
    const config = parseConfig(form);
    const state = {
        selectedRegion: config.selectedRegion ?? '',
        selectedDistrict: config.selectedDistrict ?? '',
        selectedCouncil: config.selectedCouncil ?? '',
        selectedWard: config.selectedWard ?? '',
        selectedStreet: config.selectedStreet ?? '',
        selectedFiscalYear: config.selectedFiscalYear ?? '',
        defaultFiscalYear: config.defaultFiscalYear ?? 'all',
        selectedPeriod: config.selectedPeriod ?? 'annually',
        selectedDateFrom: config.selectedDateFrom ?? '',
        selectedDateTo: config.selectedDateTo ?? '',
        selectedSort: config.selectedSort ?? 'newest',
        selectedPrimary: config.selectedPrimary ?? '',
        selectedAgeMin: config.selectedAgeMin ?? '',
        selectedAgeMax: config.selectedAgeMax ?? '',
        useCustomDates: config.useCustomDates ?? '',
        revealTimeFilters: Boolean(config.revealTimeFilters),
        fiscalYearTouched: Boolean(config.revealTimeFilters),
        periodTouched: Boolean(config.revealTimeFilters),
        datesTouched: Boolean(config.revealTimeFilters),
        primaryTouched: Boolean(config.revealTimeFilters),
        ageMinTouched: Boolean(config.revealTimeFilters),
        ageMaxTouched: Boolean(config.revealTimeFilters),
        hasFiscalYear: config.hasFiscalYear !== false,
        hasPeriod: config.hasPeriod !== false,
        submitOnPeriodChange: Boolean(config.submitOnPeriodChange),
        hasDates: config.hasDates !== false,
        hasSort: config.hasSort !== false,
        hasAge: Boolean(config.hasAge),
        includeStreet: config.includeStreet !== false,
        geoApi: config.geoApi ?? {},
        locks: config.locks ?? {},
        districts: [],
        councils: [],
        wards: [],
        streets: [],
    };

    const field = (name) => form.querySelector(`[name="${name}"]`);
    const wrap = (name) => field(name)?.closest('.wizard-field');

    const isLocked = (key) => Boolean(state.locks?.[key]);

    const showDistrict = () => Boolean(state.selectedRegion) || isLocked('district_id');
    const showCouncil = () => Boolean(state.selectedDistrict) || isLocked('council_id');
    const showWard = () => Boolean(state.selectedCouncil) || isLocked('ward_id');
    const showStreet = () => state.includeStreet && Boolean(state.selectedWard);
    const showDates = () => {
        if (! state.hasDates) {
            return false;
        }
        if (state.hasPeriod) {
            return state.periodTouched || state.revealTimeFilters;
        }
        if (state.hasFiscalYear) {
            return state.fiscalYearTouched || state.revealTimeFilters;
        }
        return state.primaryTouched || state.revealTimeFilters;
    };
    const showAgeMax = () => state.hasAge && (state.ageMinTouched || state.revealTimeFilters);
    const showSort = () => {
        if (! state.hasSort) {
            return false;
        }
        if (state.hasAge) {
            return showAgeMax() && (state.ageMaxTouched || state.revealTimeFilters);
        }
        if (state.hasDates) {
            return showDates() && (state.datesTouched || state.revealTimeFilters);
        }
        if (state.hasPeriod) {
            return state.periodTouched || state.revealTimeFilters;
        }
        if (state.hasFiscalYear) {
            return state.fiscalYearTouched || state.revealTimeFilters;
        }
        return state.primaryTouched || state.revealTimeFilters;
    };

    const refreshSelect = (id) => {
        queueMicrotask(() => {
            const select = form.querySelector(`#${id}`) || document.getElementById(id);
            if (select) {
                window.AppSelect?.refreshAppSelect(select);
            }
        });
    };

    const render = () => {
        setHidden(wrap('district_id'), ! showDistrict());
        setHidden(wrap('council_id'), ! showCouncil());
        setHidden(wrap('ward_id'), ! showWard());
        setHidden(wrap('street_id'), ! showStreet());
        form.querySelectorAll('[data-filter-reveal="dates"]').forEach((el) => setHidden(el, ! showDates()));
        form.querySelectorAll('[data-filter-reveal="age-max"]').forEach((el) => setHidden(el, ! showAgeMax()));
        form.querySelectorAll('[data-filter-reveal="sort"]').forEach((el) => setHidden(el, ! showSort()));
        form.querySelectorAll('[data-filter-reveal="period-extra"]').forEach((el) => setHidden(el, ! state.hasPeriod));

        const toggleClear = (selector, visible) => {
            form.querySelectorAll(selector).forEach((el) => setHidden(el, ! visible));
        };

        toggleClear('[data-filter-clear="region"]', Boolean(state.selectedRegion) && ! isLocked('region_id'));
        toggleClear('[data-filter-clear="district"]', ! isLocked('district_id') && showDistrict());
        toggleClear('[data-filter-clear="council"]', ! isLocked('council_id') && showCouncil());
        toggleClear('[data-filter-clear="ward"]', ! isLocked('ward_id') && showWard());
        toggleClear('[data-filter-clear="date_from"]', Boolean(state.selectedDateFrom));
        toggleClear('[data-filter-clear="date_to"]', Boolean(state.selectedDateTo));
        toggleClear('[data-filter-clear="primary"]', Boolean(state.selectedPrimary));
        toggleClear('[data-filter-clear="age_min"]', Boolean(state.selectedAgeMin));
        toggleClear('[data-filter-clear="age_max"]', Boolean(state.selectedAgeMax));

        form.querySelectorAll('.app-filter-control').forEach((control) => {
            const hasClear = control.querySelector('.app-filter-clear-inside:not(.d-none):not([hidden])');
            control.classList.toggle('has-clear', Boolean(hasClear));
        });

        const customDates = form.querySelector('#use_custom_dates');
        if (customDates) {
            customDates.value = state.useCustomDates;
        }
    };

    async function fetchGeo(url, target) {
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (! response.ok) {
            state[target] = [];
            return;
        }
        const data = await response.json();
        const items = data?.data ?? data;
        state[target] = Array.isArray(items) ? items : [];

        const scrollPosition = captureScrollPosition();
        const map = {
            districts: ['district_id', state.selectedDistrict, form.querySelector('#district_id option[value=""]')?.textContent],
            councils: ['council_id', state.selectedCouncil, form.querySelector('#council_id option[value=""]')?.textContent],
            wards: ['ward_id', state.selectedWard, form.querySelector('#ward_id option[value=""]')?.textContent],
            streets: ['street_id', state.selectedStreet, form.querySelector('#street_id option[value=""]')?.textContent],
        };
        const [id, selected, placeholder] = map[target] || [];
        fillSelect(form.querySelector(`#${id}`), state[target], selected, placeholder);
        requestAnimationFrame(() => restoreScrollPosition(scrollPosition));
    }

    const loadDistricts = (regionId) => {
        if (! regionId || ! state.geoApi.districts) {
            state.districts = [];
            fillSelect(form.querySelector('#district_id'), [], '', form.querySelector('#district_id option[value=""]')?.textContent);
            return Promise.resolve();
        }
        return fetchGeo(`${state.geoApi.districts}/${encodeURIComponent(regionId)}`, 'districts');
    };
    const loadCouncils = (districtId) => {
        if (! districtId || ! state.geoApi.councils) {
            state.councils = [];
            fillSelect(form.querySelector('#council_id'), [], '', form.querySelector('#council_id option[value=""]')?.textContent);
            return Promise.resolve();
        }
        return fetchGeo(`${state.geoApi.councils}/${encodeURIComponent(districtId)}`, 'councils');
    };
    const loadWards = (councilId) => {
        if (! councilId || ! state.geoApi.wards) {
            state.wards = [];
            fillSelect(form.querySelector('#ward_id'), [], '', form.querySelector('#ward_id option[value=""]')?.textContent);
            return Promise.resolve();
        }
        return fetchGeo(`${state.geoApi.wards}/${encodeURIComponent(councilId)}`, 'wards');
    };
    const loadStreets = (wardId) => {
        if (! wardId || ! state.geoApi.streets) {
            state.streets = [];
            fillSelect(form.querySelector('#street_id'), [], '', form.querySelector('#street_id option[value=""]')?.textContent);
            return Promise.resolve();
        }
        return fetchGeo(`${state.geoApi.streets}/${encodeURIComponent(wardId)}`, 'streets');
    };

    const onRegionChange = () => {
        if (isLocked('region_id')) {
            state.selectedRegion = String(state.locks.region_id);
            return;
        }
        state.selectedDistrict = state.locks.district_id ? String(state.locks.district_id) : '';
        state.selectedCouncil = state.locks.council_id ? String(state.locks.council_id) : '';
        state.selectedWard = state.locks.ward_id ? String(state.locks.ward_id) : '';
        state.selectedStreet = '';
        state.councils = [];
        state.wards = [];
        state.streets = [];
        loadDistricts(state.selectedRegion);
        render();
    };

    field('region_id')?.addEventListener('change', (event) => {
        state.selectedRegion = event.target.value;
        onRegionChange();
    });
    field('district_id')?.addEventListener('change', (event) => {
        if (isLocked('district_id')) {
            return;
        }
        state.selectedDistrict = event.target.value;
        state.selectedCouncil = state.locks.council_id ? String(state.locks.council_id) : '';
        state.selectedWard = state.locks.ward_id ? String(state.locks.ward_id) : '';
        state.selectedStreet = '';
        state.wards = [];
        state.streets = [];
        loadCouncils(state.selectedDistrict);
        render();
    });
    field('council_id')?.addEventListener('change', (event) => {
        if (isLocked('council_id')) {
            return;
        }
        state.selectedCouncil = event.target.value;
        state.selectedWard = state.locks.ward_id ? String(state.locks.ward_id) : '';
        state.selectedStreet = '';
        state.streets = [];
        loadWards(state.selectedCouncil);
        render();
    });
    field('ward_id')?.addEventListener('change', (event) => {
        if (isLocked('ward_id')) {
            return;
        }
        state.selectedWard = event.target.value;
        state.selectedStreet = '';
        loadStreets(state.selectedWard);
        render();
    });
    field('street_id')?.addEventListener('change', (event) => {
        state.selectedStreet = event.target.value;
        render();
    });
    field('fiscal_year')?.addEventListener('change', (event) => {
        state.selectedFiscalYear = event.target.value;
        state.fiscalYearTouched = true;
        state.useCustomDates = '';
        render();
    });
    field('date_from')?.addEventListener('change', (event) => {
        state.selectedDateFrom = event.target.value;
        state.datesTouched = true;
        state.useCustomDates = '1';
        render();
    });
    field('date_to')?.addEventListener('change', (event) => {
        state.selectedDateTo = event.target.value;
        state.datesTouched = true;
        state.useCustomDates = '1';
        render();
    });
    field('age_min')?.addEventListener('change', (event) => {
        state.selectedAgeMin = event.target.value;
        state.ageMinTouched = true;
        render();
        refreshSelect('age_max');
    });
    field('age_max')?.addEventListener('change', (event) => {
        state.selectedAgeMax = event.target.value;
        state.ageMaxTouched = true;
        render();
    });
    field('sort')?.addEventListener('change', (event) => {
        state.selectedSort = event.target.value;
        render();
    });

    const primary = form.querySelector(`#${config.primarySelectId || 'primary_filter'}`) || form.querySelector('[data-primary-filter]');
    primary?.addEventListener('change', (event) => {
        state.selectedPrimary = event.target.value;
        state.primaryTouched = true;
        render();
    });

    form.querySelectorAll('[data-period-value]').forEach((button) => {
        button.addEventListener('click', () => {
            state.selectedPeriod = button.dataset.periodValue;
            state.periodTouched = true;
            state.useCustomDates = '';
            const input = form.querySelector('[data-period-input]') || form.querySelector('#period');
            if (input) {
                input.value = state.selectedPeriod;
            }
            form.querySelectorAll('[data-period-value]').forEach((btn) => {
                btn.classList.toggle('is-active', btn === button);
                btn.setAttribute('aria-checked', btn === button ? 'true' : 'false');
            });
            render();
            refreshSelect('date_from');
            refreshSelect('date_to');
            if (state.submitOnPeriodChange || button.dataset.periodSubmit === '1') {
                form.requestSubmit();
            }
        });
    });

    form.addEventListener('click', (event) => {
        const clear = event.target.closest('[data-filter-clear]');
        if (! clear) {
            return;
        }
        event.preventDefault();
        const key = clear.dataset.filterClear;

        if (key === 'region' && ! isLocked('region_id')) {
            state.selectedRegion = '';
            field('region_id').value = '';
            onRegionChange();
            refreshSelect('region_id');
        } else if (key === 'district' && ! isLocked('district_id')) {
            state.selectedDistrict = '';
            if (field('district_id')) field('district_id').value = '';
            state.selectedCouncil = state.locks.council_id ? String(state.locks.council_id) : '';
            state.selectedWard = state.locks.ward_id ? String(state.locks.ward_id) : '';
            state.selectedStreet = '';
            loadCouncils('');
            render();
            refreshSelect('district_id');
        } else if (key === 'council' && ! isLocked('council_id')) {
            state.selectedCouncil = '';
            if (field('council_id')) field('council_id').value = '';
            loadWards('');
            render();
        } else if (key === 'ward' && ! isLocked('ward_id')) {
            state.selectedWard = '';
            if (field('ward_id')) field('ward_id').value = '';
            loadStreets('');
            render();
        } else if (key === 'street') {
            state.selectedStreet = '';
            if (field('street_id')) field('street_id').value = '';
            refreshSelect('street_id');
            render();
        } else if (key === 'fiscal_year') {
            state.selectedFiscalYear = state.defaultFiscalYear || 'all';
            if (field('fiscal_year')) field('fiscal_year').value = state.selectedFiscalYear;
            refreshSelect('fiscal_year');
        } else if (key === 'date_from') {
            state.selectedDateFrom = '';
            if (field('date_from')) field('date_from').value = '';
            state.useCustomDates = state.selectedDateTo ? '1' : '';
            render();
        } else if (key === 'date_to') {
            state.selectedDateTo = '';
            if (field('date_to')) field('date_to').value = '';
            state.useCustomDates = state.selectedDateFrom ? '1' : '';
            render();
        } else if (key === 'sort') {
            state.selectedSort = 'newest';
            if (field('sort')) field('sort').value = 'newest';
            refreshSelect('sort');
        } else if (key === 'primary') {
            state.selectedPrimary = '';
            if (primary) primary.value = '';
            refreshSelect(config.primarySelectId || 'primary_filter');
            render();
        } else if (key === 'age_min') {
            state.selectedAgeMin = '';
            if (field('age_min')) field('age_min').value = '';
            render();
        } else if (key === 'age_max') {
            state.selectedAgeMax = '';
            if (field('age_max')) field('age_max').value = '';
            render();
        }
    });

    async function boot() {
        if (state.locks.region_id) state.selectedRegion = String(state.locks.region_id);
        if (state.locks.district_id) state.selectedDistrict = String(state.locks.district_id);
        if (state.locks.council_id) state.selectedCouncil = String(state.locks.council_id);
        if (state.locks.ward_id) state.selectedWard = String(state.locks.ward_id);

        if (field('region_id') && state.selectedRegion) field('region_id').value = state.selectedRegion;
        if (state.selectedRegion) await loadDistricts(state.selectedRegion);
        if (state.selectedDistrict) await loadCouncils(state.selectedDistrict);
        if (state.selectedCouncil) await loadWards(state.selectedCouncil);
        if (state.selectedWard) await loadStreets(state.selectedWard);
        render();
    }

    boot();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-report-filters]').forEach(bindReportForm);

    document.querySelectorAll('[data-period-segmented]').forEach((group) => {
        if (group.closest('[data-report-filters]')) {
            return;
        }
        const form = group.closest('form');
        const input = group.querySelector('[data-period-input]');
        group.querySelectorAll('[data-period-value]').forEach((button) => {
            button.addEventListener('click', () => {
                if (input) {
                    input.value = button.dataset.periodValue;
                }
                group.querySelectorAll('[data-period-value]').forEach((btn) => {
                    btn.classList.toggle('is-active', btn === button);
                    btn.setAttribute('aria-checked', btn === button ? 'true' : 'false');
                });
                const custom = form?.querySelector('#use_custom_dates');
                if (custom) {
                    custom.value = '';
                }
                if (button.dataset.periodSubmit === '1') {
                    form?.requestSubmit();
                }
            });
        });
    });
});
