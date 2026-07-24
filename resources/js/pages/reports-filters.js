import { captureScrollPosition, restoreScrollPosition } from '../preserve-scroll';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('reportFilters', (config) => ({
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
        filtersOpen: Boolean(config.filtersOpen),
        revealTimeFilters: Boolean(config.revealTimeFilters),
        fiscalYearTouched: Boolean(config.revealTimeFilters),
        periodTouched: Boolean(config.revealTimeFilters),
        datesTouched: Boolean(config.revealTimeFilters),
        primaryTouched: Boolean(config.revealTimeFilters),
        ageMinTouched: Boolean(config.revealTimeFilters),
        ageMaxTouched: Boolean(config.revealTimeFilters),
        hasFiscalYear: config.hasFiscalYear !== false,
        hasPeriod: config.hasPeriod !== false,
        hasDates: config.hasDates !== false,
        hasSort: config.hasSort !== false,
        hasAge: Boolean(config.hasAge),
        geoApi: config.geoApi ?? {},
        locks: config.locks ?? {},
        districts: [],
        councils: [],
        wards: [],
        streets: [],

        isLocked(field) {
            return Boolean(this.locks?.[field]);
        },

        async init() {
            if (this.locks.region_id) {
                this.selectedRegion = String(this.locks.region_id);
            }
            if (this.locks.district_id) {
                this.selectedDistrict = String(this.locks.district_id);
            }
            if (this.locks.council_id) {
                this.selectedCouncil = String(this.locks.council_id);
            }
            if (this.locks.ward_id) {
                this.selectedWard = String(this.locks.ward_id);
            }

            if (this.selectedRegion) {
                await this.loadDistricts(this.selectedRegion);
            }
            if (this.selectedDistrict) {
                await this.loadCouncils(this.selectedDistrict);
            }
            if (this.selectedCouncil) {
                await this.loadWards(this.selectedCouncil);
            }
            if (this.selectedWard) {
                await this.loadStreets(this.selectedWard);
            }

            this.$watch('showPeriod', (visible) => visible && this.refreshSelect('period'));
            this.$watch('showDates', (visible) => {
                if (visible) {
                    this.refreshSelect('date_from');
                    this.refreshSelect('date_to');
                }
            });
            this.$watch('showSort', (visible) => visible && this.refreshSelect('sort'));
            this.$watch('showFiscalYear', (visible) => visible && this.refreshSelect('fiscal_year'));
            this.$watch('showAgeMax', (visible) => visible && this.refreshSelect('age_max'));
        },

        refreshSelect(id) {
            queueMicrotask(() => {
                const select = document.getElementById(id);
                if (select) {
                    window.AppSelect?.refreshAppSelect(select);
                }
            });
        },

        async fetchGeo(url, target) {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (! response.ok) {
                this[target] = [];
                return;
            }
            const data = await response.json();
            this[target] = data?.data ?? data;

            const scrollPosition = captureScrollPosition();

            queueMicrotask(() => {
                ['region_id', 'district_id', 'council_id', 'ward_id', 'street_id'].forEach((id) => {
                    const select = document.getElementById(id);
                    if (select) {
                        window.AppSelect?.refreshAppSelect(select);
                    }
                });

                requestAnimationFrame(() => {
                    restoreScrollPosition(scrollPosition);
                });
            });
        },

        loadDistricts(regionId) {
            if (! regionId || ! this.geoApi.districts) {
                this.districts = [];
                return Promise.resolve();
            }

            return this.fetchGeo(`${this.geoApi.districts}?region_id=${encodeURIComponent(regionId)}`, 'districts');
        },

        loadCouncils(districtId) {
            if (! districtId || ! this.geoApi.councils) {
                this.councils = [];
                return Promise.resolve();
            }

            return this.fetchGeo(`${this.geoApi.councils}?district_id=${encodeURIComponent(districtId)}`, 'councils');
        },

        loadWards(councilId) {
            if (! councilId || ! this.geoApi.wards) {
                this.wards = [];
                return Promise.resolve();
            }

            return this.fetchGeo(`${this.geoApi.wards}?council_id=${encodeURIComponent(councilId)}`, 'wards');
        },

        loadStreets(wardId) {
            if (! wardId || ! this.geoApi.streets) {
                this.streets = [];
                return Promise.resolve();
            }

            return this.fetchGeo(`${this.geoApi.streets}?ward_id=${encodeURIComponent(wardId)}`, 'streets');
        },

        onRegionChange() {
            if (this.isLocked('region_id')) {
                this.selectedRegion = String(this.locks.region_id);
                return;
            }
            this.selectedDistrict = this.locks.district_id ? String(this.locks.district_id) : '';
            this.selectedCouncil = this.locks.council_id ? String(this.locks.council_id) : '';
            this.selectedWard = this.locks.ward_id ? String(this.locks.ward_id) : '';
            this.selectedStreet = '';
            this.councils = [];
            this.wards = [];
            this.streets = [];
            this.loadDistricts(this.selectedRegion);
        },

        onDistrictChange() {
            if (this.isLocked('district_id')) {
                this.selectedDistrict = String(this.locks.district_id);
                return;
            }
            this.selectedCouncil = this.locks.council_id ? String(this.locks.council_id) : '';
            this.selectedWard = this.locks.ward_id ? String(this.locks.ward_id) : '';
            this.selectedStreet = '';
            this.wards = [];
            this.streets = [];
            this.loadCouncils(this.selectedDistrict);
        },

        onCouncilChange() {
            if (this.isLocked('council_id')) {
                this.selectedCouncil = String(this.locks.council_id);
                return;
            }
            this.selectedWard = this.locks.ward_id ? String(this.locks.ward_id) : '';
            this.selectedStreet = '';
            this.streets = [];
            this.loadWards(this.selectedCouncil);
        },

        onWardChange() {
            if (this.isLocked('ward_id')) {
                this.selectedWard = String(this.locks.ward_id);
                return;
            }
            this.selectedStreet = '';
            this.loadStreets(this.selectedWard);
        },

        onPrimaryChange() {
            this.primaryTouched = true;
        },

        onFiscalYearChange() {
            this.fiscalYearTouched = true;
            this.useCustomDates = '';
        },

        onPeriodChange() {
            this.periodTouched = true;
            this.useCustomDates = '';
        },

        onDateChange() {
            this.datesTouched = true;
            this.useCustomDates = '1';
        },

        onAgeMinChange() {
            this.ageMinTouched = true;
        },

        onAgeMaxChange() {
            this.ageMaxTouched = true;
        },

        clearRegion() {
            if (this.isLocked('region_id')) {
                return;
            }
            this.selectedRegion = '';
            this.onRegionChange();
            this.refreshSelect('region_id');
        },

        clearDistrict() {
            if (this.isLocked('district_id')) {
                return;
            }
            if (! this.selectedDistrict) {
                this.clearRegion();
                return;
            }
            this.selectedDistrict = '';
            this.selectedCouncil = this.locks.council_id ? String(this.locks.council_id) : '';
            this.selectedWard = this.locks.ward_id ? String(this.locks.ward_id) : '';
            this.selectedStreet = '';
            this.councils = [];
            this.wards = [];
            this.streets = [];
            this.refreshSelect('district_id');
        },

        clearCouncil() {
            if (this.isLocked('council_id')) {
                return;
            }
            if (! this.selectedCouncil) {
                this.clearDistrict();
                return;
            }
            this.selectedCouncil = '';
            this.selectedWard = this.locks.ward_id ? String(this.locks.ward_id) : '';
            this.selectedStreet = '';
            this.wards = [];
            this.streets = [];
            this.refreshSelect('council_id');
        },

        clearWard() {
            if (this.isLocked('ward_id')) {
                return;
            }
            if (! this.selectedWard) {
                this.clearCouncil();
                return;
            }
            this.selectedWard = '';
            this.selectedStreet = '';
            this.streets = [];
            this.refreshSelect('ward_id');
        },

        clearStreet() {
            if (! this.selectedStreet) {
                this.clearWard();
                return;
            }
            this.selectedStreet = '';
            this.refreshSelect('street_id');
        },

        clearPrimaryValue() {
            this.selectedPrimary = '';
            this.refreshSelect(config.primarySelectId || 'primary_filter');
        },

        clearAgeMin() {
            this.selectedAgeMin = '';
        },

        clearAgeMax() {
            this.selectedAgeMax = '';
        },

        // Independent clears: each field resets itself only (does not hide siblings).
        clearFiscalYearValue() {
            this.selectedFiscalYear = this.defaultFiscalYear || 'all';
            this.refreshSelect('fiscal_year');
        },

        clearPeriodValue() {
            this.selectedPeriod = 'annually';
            this.refreshSelect('period');
        },

        clearDateFrom() {
            this.selectedDateFrom = '';
            this.useCustomDates = this.selectedDateTo ? '1' : '';
        },

        clearDateTo() {
            this.selectedDateTo = '';
            this.useCustomDates = this.selectedDateFrom ? '1' : '';
        },

        clearSortValue() {
            this.selectedSort = 'newest';
            this.refreshSelect('sort');
        },

        get showDistrict() {
            return Boolean(this.selectedRegion) || this.isLocked('district_id');
        },

        get showCouncil() {
            return Boolean(this.selectedDistrict) || this.isLocked('council_id');
        },

        get showWard() {
            return Boolean(this.selectedCouncil) || this.isLocked('ward_id');
        },

        get showStreet() {
            return Boolean(this.selectedWard);
        },

        get showFiscalYear() {
            return this.hasFiscalYear;
        },

        get showPeriod() {
            if (! this.hasPeriod) {
                return false;
            }
            if (this.hasFiscalYear) {
                return this.showFiscalYear && (this.fiscalYearTouched || this.revealTimeFilters);
            }

            return this.primaryTouched || this.revealTimeFilters;
        },

        get showDates() {
            if (! this.hasDates) {
                return false;
            }
            if (this.hasPeriod) {
                return this.showPeriod && (this.periodTouched || this.revealTimeFilters);
            }
            if (this.hasFiscalYear) {
                return this.showFiscalYear && (this.fiscalYearTouched || this.revealTimeFilters);
            }

            return this.primaryTouched || this.revealTimeFilters;
        },

        get showAgeMin() {
            return this.hasAge;
        },

        get showAgeMax() {
            return this.hasAge && (this.ageMinTouched || this.revealTimeFilters);
        },

        get showSort() {
            if (! this.hasSort) {
                return false;
            }
            if (this.hasAge) {
                return this.showAgeMax && (this.ageMaxTouched || this.revealTimeFilters);
            }
            if (this.hasDates) {
                return this.showDates && (this.datesTouched || this.revealTimeFilters);
            }
            if (this.hasPeriod) {
                return this.showPeriod && (this.periodTouched || this.revealTimeFilters);
            }
            if (this.hasFiscalYear) {
                return this.showFiscalYear && (this.fiscalYearTouched || this.revealTimeFilters);
            }

            return this.primaryTouched || this.revealTimeFilters;
        },
    }));
});
