import { captureScrollPosition, restoreScrollPosition } from '../preserve-scroll';

function alpineData(el) {
    if (! el || ! window.Alpine?.$data) {
        return null;
    }

    try {
        return window.Alpine.$data(el);
    } catch {
        return null;
    }
}

function staffFormLabels(form) {
    const geo = form.querySelector('[data-staff-geo-section]');
    const alpine = alpineData(geo);

    return {
        roles_required: form.dataset.rolesRequired || '',
        geo_zone_required: form.dataset.geoZoneRequired || '',
        geo_zone_incomplete: form.dataset.geoZoneIncomplete || '',
        complete_section_here: form.dataset.completeSectionHere || '',
        ok: form.dataset.okLabel || 'OK',
        error: form.dataset.errorLabel || 'Error',
        ...(alpine?.labels || {}),
    };
}

function roleZoneMapFromForm(form) {
    try {
        return JSON.parse(form.dataset.roleZoneMap || '{}');
    } catch {
        return {};
    }
}

function clearResolvedHighlights(form) {
    form.querySelectorAll('[data-geo-step]').forEach((field) => {
        if (field.value) {
            field.classList.remove('border-red-400', 'ring-2', 'ring-red-300');
        }
    });

    const roleSection = form.querySelector('[data-staff-role-section]');
    if (roleSection && form.querySelector('input[name="role"]:checked')) {
        roleSection.classList.remove('ring-2', 'ring-red-400');
        const roleMsg = roleSection.querySelector('[data-staff-gap-message]');
        if (roleMsg) {
            roleMsg.hidden = true;
            roleMsg.textContent = '';
        }
    }

    const geoSection = form.querySelector('[data-staff-geo-section]');
    if (geoSection && ! geoSection.querySelector('[data-geo-step].border-red-400')) {
        const stillEmpty = findIncompleteGeoTarget(form);
        if (! stillEmpty) {
            geoSection.classList.remove('ring-2', 'ring-red-400');
            const geoMsg = geoSection.querySelector('[data-staff-gap-message]');
            if (geoMsg) {
                geoMsg.hidden = true;
                geoMsg.textContent = '';
            }
        }
    }
}

function markIncompleteSection({ section, field = null, message = '' }) {
    if (! section) {
        return;
    }

    section.classList.add('ring-2', 'ring-red-400');

    const gapMessage = section.querySelector('[data-staff-gap-message]');
    if (gapMessage && message) {
        gapMessage.hidden = false;
        gapMessage.textContent = message;
    }

    if (field) {
        field.classList.add('border-red-400', 'ring-2', 'ring-red-300');
    }

    section.scrollIntoView({ behavior: 'smooth', block: 'center' });

    window.setTimeout(() => {
        const focusTarget = field
            || section.querySelector('input[name="role"]:checked')
            || section.querySelector('select:not([disabled])')
            || section.querySelector('input[name="role"]');

        focusTarget?.focus?.({ preventScroll: true });

        // Prefer opening the custom AppSelect UI when present.
        const wrap = focusTarget?.closest?.('.app-select-wrap');
        wrap?.querySelector?.('.app-select-trigger')?.focus?.({ preventScroll: true });
    }, 200);
}

function findIncompleteGeoTarget(form) {
    const role = form.querySelector('input[name="role"]:checked')?.value;
    if (! role) {
        return null;
    }

    const map = roleZoneMapFromForm(form);
    const expected = map[role];
    if (! expected) {
        return null;
    }

    const geo = alpineData(form.querySelector('[data-staff-geo-section]'));
    geo?.syncRolesFromInputs?.();
    geo?.syncZoneFromSelection?.();

    const region = String(geo?.selectedRegion || form.querySelector('#staff_region_id')?.value || '');
    const district = String(geo?.selectedDistrict || form.querySelector('#staff_district_id')?.value || '');
    const council = String(geo?.selectedCouncil || form.querySelector('#staff_council_id')?.value || '');
    const ward = String(geo?.selectedWard || form.querySelector('#staff_ward_id')?.value || '');
    const zoneId = String(geo?.zoneId || form.querySelector('input[name="zone_id"]')?.value || '');

    if (! region) {
        return { field: document.getElementById('staff_region_id'), step: 'region' };
    }

    if ((expected === 'council' || expected === 'ward') && ! district) {
        return { field: document.getElementById('staff_district_id'), step: 'district' };
    }

    if ((expected === 'council' || expected === 'ward') && ! council) {
        return { field: document.getElementById('staff_council_id'), step: 'council' };
    }

    if (expected === 'ward' && ! ward) {
        return { field: document.getElementById('staff_ward_id'), step: 'ward' };
    }

    if (! zoneId) {
        if (expected === 'region') {
            return { field: document.getElementById('staff_region_id'), step: 'region' };
        }
        if (expected === 'council') {
            return { field: document.getElementById('staff_council_id'), step: 'council' };
        }

        return { field: document.getElementById('staff_ward_id'), step: 'ward' };
    }

    return null;
}

function validateStaffUserForm(form) {
    clearResolvedHighlights(form);

    const labels = staffFormLabels(form);
    const roleChecked = form.querySelector('input[name="role"]:checked');

    if (! roleChecked) {
        const section = form.querySelector('[data-staff-role-section]');
        const message = labels.roles_required || labels.complete_section_here
            || 'Please select one role before saving.';

        markIncompleteSection({
            section,
            field: null,
            message,
        });

        return false;
    }

    const incomplete = findIncompleteGeoTarget(form);
    if (incomplete) {
        const section = form.querySelector('[data-staff-geo-section]');
        const message = labels.geo_zone_incomplete || labels.geo_zone_required
            || labels.complete_section_here
            || 'Complete the geographic area required for this role.';

        markIncompleteSection({
            section,
            field: incomplete.field,
            message,
        });

        return false;
    }

    return true;
}

function initStaffUserFormGuards() {
    document.querySelectorAll('form[data-staff-user-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (! validateStaffUserForm(form)) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        form.addEventListener('change', (event) => {
            if (
                event.target?.matches?.('input[name="role"], input[name="roles[]"], [data-geo-step]')
            ) {
                clearResolvedHighlights(form);
            }
        });
    });
}

function focusServerValidationAnchor() {
    const form = document.querySelector('form[data-staff-user-form]');
    const anchor = document.querySelector('[data-error-anchor]');

    if (! form || ! anchor) {
        return;
    }

    window.setTimeout(() => {
        // Re-apply persistent emphasis using current form values — never wipe inputs.
        const labels = staffFormLabels(form);

        if (anchor.matches('[data-staff-role-section]') && ! form.querySelector('input[name="role"]:checked')) {
            markIncompleteSection({
                section: anchor,
                message: labels.roles_required || labels.complete_section_here,
            });

            return;
        }

        if (anchor.matches('[data-staff-geo-section]')) {
            const incomplete = findIncompleteGeoTarget(form);
            markIncompleteSection({
                section: anchor,
                field: incomplete?.field || null,
                message: labels.geo_zone_incomplete || labels.geo_zone_required || labels.complete_section_here,
            });
        }
    }, 200);
}

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
        lastZoneType: '',

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
            this.syncRolesFromInputs();
            this.lastZoneType = this.expectedZoneType;

            document.querySelectorAll('input[name="role"], input[name="roles[]"]').forEach((el) => {
                el.addEventListener('change', () => {
                    this.syncRolesFromInputs();
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

            // Re-assert boot values after options load so async lists do not blank selections.
            this.selectedRegion = boot.selectedRegion || this.selectedRegion;
            this.selectedDistrict = boot.selectedDistrict || this.selectedDistrict;
            this.selectedCouncil = boot.selectedCouncil || this.selectedCouncil;
            this.selectedWard = boot.selectedWard || this.selectedWard;
            this.syncZoneFromSelection();

            this.booting = false;
            this.refreshVisibleSelects();
        },

        syncRolesFromInputs() {
            const selectedRadio = document.querySelector('input[name="role"]:checked');

            if (selectedRadio) {
                this.selectedRoles = [selectedRadio.value];

                return;
            }

            this.selectedRoles = Array.from(document.querySelectorAll('input[name="roles[]"]:checked'))
                .map((el) => el.value);
        },

        onRolesChanged() {
            const nextType = this.expectedZoneType;
            const previousType = this.lastZoneType;
            this.lastZoneType = nextType;

            if (! nextType) {
                this.clearCascade();
                this.zoneType = '';
                this.zoneId = '';
                return;
            }

            // Only trim levels the new role does not need — never wipe a filled region
            // when the user is merely completing a missing deeper field.
            if (nextType === 'region') {
                this.selectedDistrict = '';
                this.selectedCouncil = '';
                this.selectedWard = '';
                this.districts = [];
                this.councils = [];
                this.wards = [];
            } else if (nextType === 'council') {
                this.selectedWard = '';
                this.wards = [];
            }

            // If the zone depth changed to a different branch family, keep shared parents.
            if (previousType && previousType !== nextType && nextType === 'ward' && previousType === 'region') {
                // region → ward: keep region, ask for district/council/ward next
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
            const snapshot = {
                region: this.selectedRegion,
                district: this.selectedDistrict,
                council: this.selectedCouncil,
                ward: this.selectedWard,
            };

            queueMicrotask(() => {
                // Keep Alpine model values intact across AppSelect redraws.
                this.selectedRegion = snapshot.region;
                this.selectedDistrict = snapshot.district;
                this.selectedCouncil = snapshot.council;
                this.selectedWard = snapshot.ward;
                this.syncZoneFromSelection();

                ['staff_region_id', 'staff_district_id', 'staff_council_id', 'staff_ward_id'].forEach((id) => {
                    const select = document.getElementById(id);
                    if (! select) {
                        return;
                    }

                    const key = id.replace('staff_', '').replace('_id', '');
                    const value = snapshot[key] || '';
                    if (value) {
                        select.value = value;
                    }

                    window.AppSelect?.refreshAppSelect(select);
                });

                requestAnimationFrame(() => restoreScrollPosition(scrollPosition));
            });
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    initStaffUserFormGuards();
    focusServerValidationAnchor();
});
