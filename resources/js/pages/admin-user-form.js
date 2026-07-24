import { captureScrollPosition, restoreScrollPosition } from '../preserve-scroll';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('userGeoZoneForm', (boot = {}) => ({
        selectedRoles: Array.isArray(boot.selectedRoles) ? [...boot.selectedRoles] : [],
        geoRoles: boot.geoRoles || ['cdo_ward', 'cdo_council', 'cdo_region'],
        roleZoneMap: boot.roleZoneMap || {
            cdo_ward: 'ward',
            cdo_council: 'council',
            cdo_region: 'region',
        },
        regions: boot.regions || [],
        districts: [],
        councils: [],
        wards: [],
        selectedRegion: boot.selectedRegion || '',
        selectedDistrict: boot.selectedDistrict || '',
        selectedCouncil: boot.selectedCouncil || '',
        selectedWard: boot.selectedWard || '',
        zoneType: boot.zoneType || '',
        zoneId: boot.zoneId || '',
        geoApi: boot.geoApi || {},
        labels: boot.labels || {},
        booting: true,

        get showGeo() {
            return this.primaryGeoRole !== null;
        },

        get primaryGeoRole() {
            for (const role of Object.keys(this.roleZoneMap)) {
                if (this.selectedRoles.includes(role)) {
                    return role;
                }
            }

            return null;
        },

        get expectedZoneType() {
            return this.primaryGeoRole ? this.roleZoneMap[this.primaryGeoRole] : '';
        },

        get showDistrict() {
            return this.expectedZoneType === 'council' || this.expectedZoneType === 'ward';
        },

        get showCouncil() {
            return this.expectedZoneType === 'council' || this.expectedZoneType === 'ward';
        },

        get showWard() {
            return this.expectedZoneType === 'ward';
        },

        async init() {
            this.syncRolesFromCheckboxes();
            document.querySelectorAll('input[name="roles[]"]').forEach((el) => {
                el.addEventListener('change', () => {
                    this.syncRolesFromCheckboxes();
                    this.onRolesChanged();
                });
            });

            this.syncZoneFromSelection();

            if (this.selectedRegion && this.showDistrict) {
                await this.loadDistricts(this.selectedRegion, false);
            }
            if (this.selectedDistrict && this.showCouncil) {
                await this.loadCouncils(this.selectedDistrict, false);
            }
            if (this.selectedCouncil && this.showWard) {
                await this.loadWards(this.selectedCouncil, false);
            }

            this.booting = false;
            this.refreshVisibleSelects();
        },

        syncRolesFromCheckboxes() {
            this.selectedRoles = Array.from(document.querySelectorAll('input[name="roles[]"]:checked'))
                .map((el) => el.value);
        },

        onRolesChanged() {
            if (! this.showGeo) {
                this.clearCascade();
                this.zoneType = '';
                this.zoneId = '';
                return;
            }

            this.syncZoneFromSelection();

            // Drop deeper levels that the new role does not need.
            if (! this.showWard) {
                this.selectedWard = '';
                this.wards = [];
            }
            if (! this.showCouncil) {
                this.selectedCouncil = '';
                this.councils = [];
                this.selectedDistrict = '';
                this.districts = [];
            }

            this.syncZoneFromSelection();
            this.refreshVisibleSelects();
        },

        clearCascade() {
            this.selectedRegion = '';
            this.selectedDistrict = '';
            this.selectedCouncil = '';
            this.selectedWard = '';
            this.districts = [];
            this.councils = [];
            this.wards = [];
        },

        syncZoneFromSelection() {
            const type = this.expectedZoneType;
            this.zoneType = type || '';

            if (type === 'region') {
                this.zoneId = this.selectedRegion || '';
            } else if (type === 'council') {
                this.zoneId = this.selectedCouncil || '';
            } else if (type === 'ward') {
                this.zoneId = this.selectedWard || '';
            } else {
                this.zoneId = '';
            }
        },

        async onRegionChange() {
            if (this.booting) {
                return;
            }
            this.selectedDistrict = '';
            this.selectedCouncil = '';
            this.selectedWard = '';
            this.councils = [];
            this.wards = [];
            this.districts = [];
            this.syncZoneFromSelection();

            if (this.selectedRegion && this.showDistrict) {
                await this.loadDistricts(this.selectedRegion, true);
            }
            this.refreshVisibleSelects();
        },

        async onDistrictChange() {
            if (this.booting) {
                return;
            }
            this.selectedCouncil = '';
            this.selectedWard = '';
            this.wards = [];
            this.councils = [];
            this.syncZoneFromSelection();

            if (this.selectedDistrict && this.showCouncil) {
                await this.loadCouncils(this.selectedDistrict, true);
            }
            this.refreshVisibleSelects();
        },

        async onCouncilChange() {
            if (this.booting) {
                return;
            }
            this.selectedWard = '';
            this.wards = [];
            this.syncZoneFromSelection();

            if (this.selectedCouncil && this.showWard) {
                await this.loadWards(this.selectedCouncil, true);
            }
            this.refreshVisibleSelects();
        },

        onWardChange() {
            this.syncZoneFromSelection();
        },

        async fetchGeo(url, target) {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (! response.ok) {
                this[target] = [];
                return;
            }
            const data = await response.json();
            this[target] = Array.isArray(data) ? data : (data?.data ?? []);
        },

        loadDistricts(regionId, refresh = true) {
            if (! regionId || ! this.geoApi.districts) {
                this.districts = [];
                return Promise.resolve();
            }

            return this.fetchGeo(`${this.geoApi.districts}/${encodeURIComponent(regionId)}`, 'districts')
                .then(() => refresh && this.refreshVisibleSelects());
        },

        loadCouncils(districtId, refresh = true) {
            if (! districtId || ! this.geoApi.councils) {
                this.councils = [];
                return Promise.resolve();
            }

            return this.fetchGeo(`${this.geoApi.councils}/${encodeURIComponent(districtId)}`, 'councils')
                .then(() => refresh && this.refreshVisibleSelects());
        },

        loadWards(councilId, refresh = true) {
            if (! councilId || ! this.geoApi.wards) {
                this.wards = [];
                return Promise.resolve();
            }

            return this.fetchGeo(`${this.geoApi.wards}/${encodeURIComponent(councilId)}`, 'wards')
                .then(() => refresh && this.refreshVisibleSelects());
        },

        refreshVisibleSelects() {
            const scrollPosition = captureScrollPosition();

            queueMicrotask(() => {
                ['staff_region_id', 'staff_district_id', 'staff_council_id', 'staff_ward_id'].forEach((id) => {
                    const select = document.getElementById(id);
                    if (select) {
                        window.AppSelect?.refreshAppSelect(select);
                    }
                });

                requestAnimationFrame(() => restoreScrollPosition(scrollPosition));
            });
        },
    }));
});
