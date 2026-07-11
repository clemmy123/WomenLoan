import { captureScrollPosition, restoreScrollPosition } from '../preserve-scroll';

document.addEventListener('alpine:init', () => {
    Alpine.data('reportFilters', (config) => ({
        selectedRegion: config.selectedRegion ?? '',
        selectedDistrict: config.selectedDistrict ?? '',
        selectedCouncil: config.selectedCouncil ?? '',
        selectedWard: config.selectedWard ?? '',
        selectedStreet: config.selectedStreet ?? '',
        filtersOpen: Boolean(config.filtersOpen),
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
        },

        async fetchGeo(url, target) {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
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
                    requestAnimationFrame(() => restoreScrollPosition(scrollPosition));
                });
            });
        },

        async loadDistricts(regionId) {
            this.districts = [];
            if (!regionId) return;
            await this.fetchGeo(`${this.geoApi.districts}/${regionId}`, 'districts');
        },

        async loadCouncils(districtId) {
            this.councils = [];
            if (!districtId) return;
            await this.fetchGeo(`${this.geoApi.councils}/${districtId}`, 'councils');
        },

        async loadWards(councilId) {
            this.wards = [];
            if (!councilId) return;
            await this.fetchGeo(`${this.geoApi.wards}/${councilId}`, 'wards');
        },

        async loadStreets(wardId) {
            this.streets = [];
            if (!wardId) return;
            await this.fetchGeo(`${this.geoApi.streets}/${wardId}`, 'streets');
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
    }));
});
