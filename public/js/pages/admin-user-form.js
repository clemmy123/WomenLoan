import { captureScrollPosition, restoreScrollPosition } from '../preserve-scroll.js';
import { fetchGeoChildren, fillSelect, resetSelect } from '../geo-cascade.js';

const staffGeoControllers = new WeakMap();

function staffGeoController(section) {
    return staffGeoControllers.get(section) || null;
}

function staffFormLabels(form) {
    const geo = form.querySelector('[data-staff-geo-section]');
    const controller = staffGeoController(geo);

    return {
        roles_required: form.dataset.rolesRequired || '',
        geo_zone_required: form.dataset.geoZoneRequired || '',
        geo_zone_incomplete: form.dataset.geoZoneIncomplete || '',
        complete_section_here: form.dataset.completeSectionHere || '',
        ok: form.dataset.okLabel || 'OK',
        error: form.dataset.errorLabel || 'Error',
        ...(controller?.labels || {}),
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

    const geo = form.querySelector('[data-staff-geo-section]');
    const controller = staffGeoController(geo);
    controller?.syncRolesFromInputs?.();
    controller?.syncZoneFromSelection?.();

    const region = String(form.querySelector('#staff_region_id')?.value || '');
    const district = String(form.querySelector('#staff_district_id')?.value || '');
    const council = String(form.querySelector('#staff_council_id')?.value || '');
    const ward = String(form.querySelector('#staff_ward_id')?.value || '');
    const zoneId = String(form.querySelector('input[name="zone_id"]')?.value || '');

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

function createStaffGeoController(section, boot = {}) {
    const state = {
        selectedRoles: Array.isArray(boot.selectedRoles) ? [...boot.selectedRoles] : [],
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
    };

    const regionSelect = section.querySelector('#staff_region_id');
    const districtSelect = section.querySelector('#staff_district_id');
    const councilSelect = section.querySelector('#staff_council_id');
    const wardSelect = section.querySelector('#staff_ward_id');
    const districtWrap = section.querySelector('[data-geo-wrap="district"]');
    const councilWrap = section.querySelector('[data-geo-wrap="council"]');
    const wardWrap = section.querySelector('[data-geo-wrap="ward"]');
    const hintEl = section.querySelector('[data-staff-geo-hint]');
    const requiredNote = section.querySelector('[data-staff-geo-required-note]');
    const hiddenZoneType = section.querySelector('[data-staff-zone-type]');
    const hiddenZoneId = section.querySelector('[data-staff-zone-id]');
    const hiddenRegion = section.querySelector('[data-staff-cascade-region]');
    const hiddenDistrict = section.querySelector('[data-staff-cascade-district]');
    const hiddenCouncil = section.querySelector('[data-staff-cascade-council]');
    const hiddenWard = section.querySelector('[data-staff-cascade-ward]');

    const primaryGeoRole = () => {
        for (const role of Object.keys(state.roleZoneMap)) {
            if (state.selectedRoles.includes(role)) {
                return role;
            }
        }

        return null;
    };

    const expectedZoneType = () => {
        const role = primaryGeoRole();
        return role ? state.roleZoneMap[role] : '';
    };

    const showGeo = () => primaryGeoRole() !== null;
    const showDistrict = () => expectedZoneType() === 'council' || expectedZoneType() === 'ward';
    const showCouncil = () => expectedZoneType() === 'council' || expectedZoneType() === 'ward';
    const showWard = () => expectedZoneType() === 'ward';

    const syncVisibility = () => {
        const visible = showGeo();
        section.hidden = ! visible;

        if (districtWrap) {
            districtWrap.hidden = ! showDistrict();
        }
        if (councilWrap) {
            councilWrap.hidden = ! showCouncil();
        }
        if (wardWrap) {
            wardWrap.hidden = ! showWard();
        }
        if (requiredNote) {
            requiredNote.hidden = ! visible;
        }
        if (hintEl && state.labels.geo_hint) {
            hintEl.textContent = state.labels.geo_hint;
        }

        if (regionSelect) {
            regionSelect.required = visible;
        }
        if (districtSelect) {
            districtSelect.required = showDistrict();
            districtSelect.disabled = ! state.selectedRegion;
        }
        if (councilSelect) {
            councilSelect.required = showCouncil();
            councilSelect.disabled = ! state.selectedDistrict;
        }
        if (wardSelect) {
            wardSelect.required = showWard();
            wardSelect.disabled = ! state.selectedCouncil;
        }
    };

    const syncHiddenFields = () => {
        if (hiddenZoneType) {
            hiddenZoneType.value = state.zoneType || '';
        }
        if (hiddenZoneId) {
            hiddenZoneId.value = state.zoneId || '';
        }
        if (hiddenRegion) {
            hiddenRegion.value = state.selectedRegion || '';
        }
        if (hiddenDistrict) {
            hiddenDistrict.value = state.selectedDistrict || '';
        }
        if (hiddenCouncil) {
            hiddenCouncil.value = state.selectedCouncil || '';
        }
        if (hiddenWard) {
            hiddenWard.value = state.selectedWard || '';
        }
    };

    const refreshVisibleSelects = () => {
        const scrollPosition = captureScrollPosition();
        const snapshot = {
            region: state.selectedRegion,
            district: state.selectedDistrict,
            council: state.selectedCouncil,
            ward: state.selectedWard,
        };

        queueMicrotask(() => {
            state.selectedRegion = snapshot.region;
            state.selectedDistrict = snapshot.district;
            state.selectedCouncil = snapshot.council;
            state.selectedWard = snapshot.ward;
            controller.syncZoneFromSelection();

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
    };

    const loadDistricts = async (regionId, refresh = true) => {
        if (! regionId || ! state.geoApi.districts || ! districtSelect) {
            state.districts = [];
            return;
        }

        const items = await fetchGeoChildren(state.geoApi, 'districts', regionId).catch(() => []);
        state.districts = items;
        resetSelect(districtSelect, state.labels.district || 'Select District', false);
        fillSelect(districtSelect, items, state.selectedDistrict);
        if (refresh) {
            refreshVisibleSelects();
        }
    };

    const loadCouncils = async (districtId, refresh = true) => {
        if (! districtId || ! state.geoApi.councils || ! councilSelect) {
            state.councils = [];
            return;
        }

        const items = await fetchGeoChildren(state.geoApi, 'councils', districtId).catch(() => []);
        state.councils = items;
        resetSelect(councilSelect, state.labels.council || 'Select Council', false);
        fillSelect(councilSelect, items, state.selectedCouncil);
        if (refresh) {
            refreshVisibleSelects();
        }
    };

    const loadWards = async (councilId, refresh = true) => {
        if (! councilId || ! state.geoApi.wards || ! wardSelect) {
            state.wards = [];
            return;
        }

        const items = await fetchGeoChildren(state.geoApi, 'wards', councilId).catch(() => []);
        state.wards = items;
        resetSelect(wardSelect, state.labels.ward || 'Select Ward', false);
        fillSelect(wardSelect, items, state.selectedWard);
        if (refresh) {
            refreshVisibleSelects();
        }
    };

    const controller = {
        ...state,

        syncRolesFromInputs() {
            const selectedRadio = document.querySelector('input[name="role"]:checked');

            if (selectedRadio) {
                state.selectedRoles = [selectedRadio.value];

                return;
            }

            state.selectedRoles = Array.from(document.querySelectorAll('input[name="roles[]"]:checked'))
                .map((el) => el.value);
        },

        syncZoneFromSelection() {
            const type = expectedZoneType();
            state.zoneType = type || '';

            if (type === 'region') {
                state.zoneId = state.selectedRegion || '';
            } else if (type === 'council') {
                state.zoneId = state.selectedCouncil || '';
            } else if (type === 'ward') {
                state.zoneId = state.selectedWard || '';
            } else {
                state.zoneId = '';
            }

            syncHiddenFields();
            syncVisibility();
        },

        clearCascade() {
            state.selectedRegion = '';
            state.selectedDistrict = '';
            state.selectedCouncil = '';
            state.selectedWard = '';
            state.districts = [];
            state.councils = [];
            state.wards = [];
        },

        onRolesChanged() {
            const nextType = expectedZoneType();
            const previousType = state.lastZoneType;
            state.lastZoneType = nextType;

            if (! nextType) {
                controller.clearCascade();
                state.zoneType = '';
                state.zoneId = '';
                controller.syncZoneFromSelection();
                return;
            }

            if (nextType === 'region') {
                state.selectedDistrict = '';
                state.selectedCouncil = '';
                state.selectedWard = '';
                state.districts = [];
                state.councils = [];
                state.wards = [];
            } else if (nextType === 'council') {
                state.selectedWard = '';
                state.wards = [];
            }

            if (previousType && previousType !== nextType && nextType === 'ward' && previousType === 'region') {
                // keep region when deepening zone requirements
            }

            controller.syncZoneFromSelection();
            refreshVisibleSelects();
        },

        async onRegionChange() {
            if (state.booting) {
                return;
            }

            state.selectedRegion = regionSelect?.value || '';
            state.selectedDistrict = '';
            state.selectedCouncil = '';
            state.selectedWard = '';
            state.councils = [];
            state.wards = [];
            state.districts = [];
            controller.syncZoneFromSelection();

            if (state.selectedRegion && showDistrict()) {
                await loadDistricts(state.selectedRegion, true);
            }
            refreshVisibleSelects();
        },

        async onDistrictChange() {
            if (state.booting) {
                return;
            }

            state.selectedDistrict = districtSelect?.value || '';
            state.selectedCouncil = '';
            state.selectedWard = '';
            state.wards = [];
            state.councils = [];
            controller.syncZoneFromSelection();

            if (state.selectedDistrict && showCouncil()) {
                await loadCouncils(state.selectedDistrict, true);
            }
            refreshVisibleSelects();
        },

        async onCouncilChange() {
            if (state.booting) {
                return;
            }

            state.selectedCouncil = councilSelect?.value || '';
            state.selectedWard = '';
            state.wards = [];
            controller.syncZoneFromSelection();

            if (state.selectedCouncil && showWard()) {
                await loadWards(state.selectedCouncil, true);
            }
            refreshVisibleSelects();
        },

        onWardChange() {
            state.selectedWard = wardSelect?.value || '';
            controller.syncZoneFromSelection();
        },
    };

    staffGeoControllers.set(section, controller);

    regionSelect?.addEventListener('change', () => controller.onRegionChange());
    districtSelect?.addEventListener('change', () => controller.onDistrictChange());
    councilSelect?.addEventListener('change', () => controller.onCouncilChange());
    wardSelect?.addEventListener('change', () => controller.onWardChange());

    document.querySelectorAll('input[name="role"], input[name="roles[]"]').forEach((el) => {
        el.addEventListener('change', () => {
            controller.syncRolesFromInputs();
            controller.onRolesChanged();
        });
    });

    const init = async () => {
        controller.syncRolesFromInputs();
        state.lastZoneType = expectedZoneType();
        controller.syncZoneFromSelection();

        if (state.selectedRegion && showDistrict()) {
            await loadDistricts(state.selectedRegion, false);
        }
        if (state.selectedDistrict && showCouncil()) {
            await loadCouncils(state.selectedDistrict, false);
        }
        if (state.selectedCouncil && showWard()) {
            await loadWards(state.selectedCouncil, false);
        }

        state.selectedRegion = boot.selectedRegion || state.selectedRegion;
        state.selectedDistrict = boot.selectedDistrict || state.selectedDistrict;
        state.selectedCouncil = boot.selectedCouncil || state.selectedCouncil;
        state.selectedWard = boot.selectedWard || state.selectedWard;
        controller.syncZoneFromSelection();

        state.booting = false;
        refreshVisibleSelects();
    };

    init();

    return controller;
}

function initStaffGeoZoneForms() {
    document.querySelectorAll('[data-staff-geo-section]').forEach((section) => {
        if (section.dataset.bound === 'true') {
            return;
        }

        let boot = {};
        try {
            boot = JSON.parse(section.dataset.staffGeoBoot || '{}');
        } catch {
            boot = {};
        }

        createStaffGeoController(section, boot);
        section.dataset.bound = 'true';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initStaffGeoZoneForms();
    initStaffUserFormGuards();
    focusServerValidationAnchor();
});
