import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('userGeoZoneForm', (boot = {}) => ({
        zoneType: boot.zoneType || '',
        labels: boot.labels || {},
        selectedRoles: Array.isArray(boot.selectedRoles) ? [...boot.selectedRoles] : [],

        init() {
            this.syncRolesFromCheckboxes();
            document.querySelectorAll('input[name="roles[]"]').forEach((el) => {
                el.addEventListener('change', () => this.syncRolesFromCheckboxes());
            });
        },

        syncRolesFromCheckboxes() {
            this.selectedRoles = Array.from(document.querySelectorAll('input[name="roles[]"]:checked'))
                .map((el) => el.value);
        },

        get emptyZoneLabel() {
            const roles = this.selectedRoles;
            const geoRoles = this.labels.geo_roles || [];
            const ministryRoles = this.labels.ministry_roles || [];
            const hasGeo = geoRoles.some((role) => roles.includes(role));

            if (hasGeo) {
                return this.labels.none;
            }

            if (roles.includes(this.labels.km_role)) {
                return this.labels.permanent_secretary;
            }

            if (ministryRoles.some((role) => roles.includes(role))) {
                return this.labels.ministry_level;
            }

            return this.labels.none;
        },
    }));
});
