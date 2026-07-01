function readWizardConfig() {
    const el = document.getElementById('loan-wizard-config');

    if (!el) {
        return {};
    }

    return JSON.parse(el.textContent);
}

function stepLabel(template, step, total) {
    return template.replace(':step', String(step)).replace(':total', String(total));
}

function normalizeId(id) {
    return id === null || id === undefined || id === '' ? '' : String(id);
}

document.addEventListener('alpine:init', () => {
    Alpine.data('loanWizard', () => {
        const config = readWizardConfig();

        return {
            step: config.step ?? 1,
            totalSteps: config.totalSteps ?? 7,
            selectedRegion: normalizeId(config.selectedRegion),
            selectedDistrict: normalizeId(config.selectedDistrict),
            selectedCouncil: normalizeId(config.selectedCouncil),
            selectedWard: normalizeId(config.selectedWard),
            selectedStreet: normalizeId(config.selectedStreet),
            loanType: config.loanType ?? '',
            i18n: config.i18n ?? {},

            // FIX: Added geoApi property to prevent 'undefined' error
            geoApi: config.geoApi ?? {
                districts: '/api/districts',
                councils: '/api/councils',
                wards: '/api/wards',
                streets: '/api/streets'
            },

            districts: [],
            councils: [],
            wards: [],
            streets: [],
            loading: false,
            error: null,

            get stepText() {
                return stepLabel(this.i18n.step ?? 'Step :step / :total', this.step, this.totalSteps);
            },

            async init() {
                if (this.selectedRegion) {
                    await this.loadDistricts(this.selectedRegion, { preserveChildren: true });
                }
                if (this.selectedDistrict) {
                    await this.loadCouncils(this.selectedDistrict, { preserveChildren: true });
                }
                if (this.selectedCouncil) {
                    await this.loadWards(this.selectedCouncil, { preserveChildren: true });
                }
                if (this.selectedWard) {
                    await this.loadStreets(this.selectedWard);
                }
            },

            async fetchData(url, target) {
                this.loading = true;
                this.error = null;

                try {
                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error(`Request failed with status ${response.status}`);
                    }

                    const data = await response.json();
                    this[target] = data?.data ?? data;
                } catch (e) {
                    console.error(e);
                    this.error = this.i18n.load_failed ?? 'Failed to load data';
                } finally {
                    this.loading = false;
                }
            },

            async loadDistricts(regionId, options = {}) {
                const { preserveChildren = false } = options;

                this.districts = [];

                if (!preserveChildren) {
                    this.councils = [];
                    this.wards = [];
                    this.streets = [];
                    this.selectedDistrict = '';
                    this.selectedCouncil = '';
                    this.selectedWard = '';
                    this.selectedStreet = '';
                }

                if (!regionId) {
                    return;
                }

                await this.fetchData(`${this.geoApi.districts}/${regionId}`, 'districts');
            },

            async loadCouncils(districtId, options = {}) {
                const { preserveChildren = false } = options;

                this.councils = [];

                if (!preserveChildren) {
                    this.wards = [];
                    this.streets = [];
                    this.selectedCouncil = '';
                    this.selectedWard = '';
                    this.selectedStreet = '';
                }

                if (!districtId) {
                    return;
                }

                await this.fetchData(`${this.geoApi.councils}/${districtId}`, 'councils');
            },

            async loadWards(councilId, options = {}) {
                const { preserveChildren = false } = options;

                this.wards = [];

                if (!preserveChildren) {
                    this.streets = [];
                    this.selectedWard = '';
                    this.selectedStreet = '';
                }

                if (!councilId) {
                    return;
                }

                await this.fetchData(`${this.geoApi.wards}/${councilId}`, 'wards');
            },

            async loadStreets(wardId) {
                this.streets = [];

                if (!wardId) {
                    return;
                }

                await this.fetchData(`${this.geoApi.streets}/${wardId}`, 'streets');
            },

            validateStep(step) {
                const form = this.$root.querySelector('form');
                if (!form) {
                    return true;
                }

                const panel = form.querySelector(`[data-wizard-step="${step}"]`);
                if (!panel) {
                    return true;
                }

                const fields = panel.querySelectorAll('input, select, textarea');
                for (const field of fields) {
                    if (!field.checkValidity()) {
                        field.reportValidity();
                        return false;
                    }
                }

                return true;
            },

            nextStep() {
                if (!this.validateStep(this.step)) {
                    return;
                }

                if (this.step < this.totalSteps) {
                    this.step++;
                }
            },

            prepareDraftSubmit() {
                const form = this.$root.querySelector('form');
                const stepInput = form?.querySelector('input[name="step"]');

                if (stepInput) {
                    stepInput.value = this.step;
                }
            },
        };
    });
});