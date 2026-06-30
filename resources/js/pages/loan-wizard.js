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

document.addEventListener('alpine:init', () => {
    Alpine.data('loanWizard', () => {
        const config = readWizardConfig();

        return {
            step: config.step ?? 1,
            totalSteps: config.totalSteps ?? 7,
            selectedRegion: config.selectedRegion ?? null,
            selectedDistrict: config.selectedDistrict ?? null,
            selectedCouncil: config.selectedCouncil ?? null,
            selectedWard: config.selectedWard ?? null,
            selectedStreet: config.selectedStreet ?? null,
            geoApi: config.geoApi ?? {},
            i18n: config.i18n ?? {},
            districts: [],
            councils: [],
            wards: [],
            streets: [],
            loading: false,
            error: null,

            get stepText() {
                return stepLabel(this.i18n.step ?? 'Step :step / :total', this.step, this.totalSteps);
            },

            init() {
                if (this.selectedRegion) {
                    this.loadDistricts(this.selectedRegion);
                }
                if (this.selectedDistrict) {
                    this.loadCouncils(this.selectedDistrict);
                }
                if (this.selectedCouncil) {
                    this.loadWards(this.selectedCouncil);
                }
                if (this.selectedWard) {
                    this.loadStreets(this.selectedWard);
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

            loadDistricts(regionId) {
                this.districts = [];
                this.councils = [];
                this.wards = [];
                this.streets = [];

                if (!regionId) {
                    return;
                }

                this.fetchData(`${this.geoApi.districts}/${regionId}`, 'districts');
            },

            loadCouncils(districtId) {
                this.councils = [];
                this.wards = [];
                this.streets = [];

                if (!districtId) {
                    return;
                }

                this.fetchData(`${this.geoApi.councils}/${districtId}`, 'councils');
            },

            loadWards(councilId) {
                this.wards = [];
                this.streets = [];

                if (!councilId) {
                    return;
                }

                this.fetchData(`${this.geoApi.wards}/${councilId}`, 'wards');
            },

            loadStreets(wardId) {
                this.streets = [];

                if (!wardId) {
                    return;
                }

                this.fetchData(`${this.geoApi.streets}/${wardId}`, 'streets');
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
