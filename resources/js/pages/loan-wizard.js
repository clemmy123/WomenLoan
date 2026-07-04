import { captureScrollPosition, restoreScrollPosition } from '../preserve-scroll';

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

const BUSINESS_GEO_SELECTS = ['region_id', 'district_id', 'council_id', 'ward_id', 'street_id'];
const GUARANTOR_GEO_SELECTS = [
    'guarantor_region_id',
    'guarantor_district_id',
    'guarantor_council_id',
    'guarantor_ward_id',
    'guarantor_street_id',
];

function refreshGeoSelects(ids) {
    ids.forEach((id) => {
        const select = document.getElementById(id);

        if (select) {
            window.AppSelect?.refreshAppSelect(select);
        }
    });
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
            guarantorRegion: normalizeId(config.guarantorRegion),
            guarantorDistrict: normalizeId(config.guarantorDistrict),
            guarantorCouncil: normalizeId(config.guarantorCouncil),
            guarantorWard: normalizeId(config.guarantorWard),
            guarantorStreet: normalizeId(config.guarantorStreet),
            loanType: config.loanType ?? '',
            selectedBusinessSector: config.selectedBusinessSector ?? '',
            selectedBusinessType: config.selectedBusinessType ?? '',
            businessCatalog: config.businessCatalog ?? [],
            i18n: config.i18n ?? {},

            geoApi: config.geoApi ?? {
                districts: '/api/districts',
                councils: '/api/councils',
                wards: '/api/wards',
                streets: '/api/streets',
            },

            districts: [],
            councils: [],
            wards: [],
            streets: [],
            guarantorDistricts: [],
            guarantorCouncils: [],
            guarantorWards: [],
            guarantorStreets: [],
            loading: false,
            guarantorLoading: false,
            error: null,

            get stepText() {
                return stepLabel(this.i18n.step ?? 'Step :step / :total', this.step, this.totalSteps);
            },

            get filteredBusinessTypes() {
                const sector = this.businessCatalog.find(
                    (entry) => entry.name === this.selectedBusinessSector,
                );

                return sector?.types ?? [];
            },

            onBusinessSectorChange() {
                this.selectedBusinessType = '';

                queueMicrotask(() => {
                    const select = document.getElementById('business_type');

                    if (select) {
                        window.AppSelect?.refreshAppSelect(select);
                    }
                });
            },

            refreshBusinessSelects() {
                ['business_sector', 'business_type'].forEach((id) => {
                    const select = document.getElementById(id);

                    if (select) {
                        window.AppSelect?.refreshAppSelect(select);
                    }
                });
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

                if (this.guarantorRegion) {
                    await this.loadGuarantorDistricts(this.guarantorRegion, { preserveChildren: true });
                }
                if (this.guarantorDistrict) {
                    await this.loadGuarantorCouncils(this.guarantorDistrict, { preserveChildren: true });
                }
                if (this.guarantorCouncil) {
                    await this.loadGuarantorWards(this.guarantorCouncil, { preserveChildren: true });
                }
                if (this.guarantorWard) {
                    await this.loadGuarantorStreets(this.guarantorWard);
                }

                queueMicrotask(() => this.refreshBusinessSelects());
            },

            async fetchData(url, target, options = {}) {
                const {
                    loadingKey = 'loading',
                    refreshIds = BUSINESS_GEO_SELECTS,
                } = options;

                this[loadingKey] = true;
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
                    this[loadingKey] = false;

                    const scrollPosition = captureScrollPosition();

                    queueMicrotask(() => {
                        refreshGeoSelects(refreshIds);

                        requestAnimationFrame(() => {
                            restoreScrollPosition(scrollPosition);
                            requestAnimationFrame(() => restoreScrollPosition(scrollPosition));
                        });
                    });
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

            async loadGuarantorDistricts(regionId, options = {}) {
                const { preserveChildren = false } = options;

                this.guarantorDistricts = [];

                if (!preserveChildren) {
                    this.guarantorCouncils = [];
                    this.guarantorWards = [];
                    this.guarantorStreets = [];
                    this.guarantorDistrict = '';
                    this.guarantorCouncil = '';
                    this.guarantorWard = '';
                    this.guarantorStreet = '';
                }

                if (!regionId) {
                    return;
                }

                await this.fetchData(
                    `${this.geoApi.districts}/${regionId}`,
                    'guarantorDistricts',
                    { loadingKey: 'guarantorLoading', refreshIds: GUARANTOR_GEO_SELECTS },
                );
            },

            async loadGuarantorCouncils(districtId, options = {}) {
                const { preserveChildren = false } = options;

                this.guarantorCouncils = [];

                if (!preserveChildren) {
                    this.guarantorWards = [];
                    this.guarantorStreets = [];
                    this.guarantorCouncil = '';
                    this.guarantorWard = '';
                    this.guarantorStreet = '';
                }

                if (!districtId) {
                    return;
                }

                await this.fetchData(
                    `${this.geoApi.councils}/${districtId}`,
                    'guarantorCouncils',
                    { loadingKey: 'guarantorLoading', refreshIds: GUARANTOR_GEO_SELECTS },
                );
            },

            async loadGuarantorWards(councilId, options = {}) {
                const { preserveChildren = false } = options;

                this.guarantorWards = [];

                if (!preserveChildren) {
                    this.guarantorStreets = [];
                    this.guarantorWard = '';
                    this.guarantorStreet = '';
                }

                if (!councilId) {
                    return;
                }

                await this.fetchData(
                    `${this.geoApi.wards}/${councilId}`,
                    'guarantorWards',
                    { loadingKey: 'guarantorLoading', refreshIds: GUARANTOR_GEO_SELECTS },
                );
            },

            async loadGuarantorStreets(wardId) {
                this.guarantorStreets = [];

                if (!wardId) {
                    return;
                }

                await this.fetchData(
                    `${this.geoApi.streets}/${wardId}`,
                    'guarantorStreets',
                    { loadingKey: 'guarantorLoading', refreshIds: GUARANTOR_GEO_SELECTS },
                );
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

                window.syncPhoneFields?.(panel);
                window.syncAmountFields?.(panel);

                const maxBytes = 1024 * 1024;
                const fields = panel.querySelectorAll('input, select, textarea');

                for (const field of fields) {
                    if (field.disabled) {
                        continue;
                    }

                    const scopeRoot = field.closest('[data-loan-scope]');
                    if (scopeRoot) {
                        const scope = scopeRoot.dataset.loanScope;
                        if (scope === 'group' && this.loanType !== 'group') {
                            continue;
                        }
                    }

                    if (field.type === 'file') {
                        const card = field.closest('.doc-attachment-card--upload');
                        const file = field.files?.[0];
                        const hasExisting = field.dataset.hasExisting === 'true';
                        const mustUpload = field.required || field.dataset.docRequired === 'true';

                        card?.classList.remove('doc-attachment-card--error');

                        if (mustUpload && !file && !hasExisting) {
                            const message = this.i18n.document_required ?? 'Please upload this document to continue.';
                            field.setCustomValidity(message);
                            field.reportValidity();
                            card?.classList.add('doc-attachment-card--error');

                            return false;
                        }

                        if (file && file.size > maxBytes) {
                            const message = this.i18n.file_too_large ?? 'File must not exceed 1MB.';
                            field.setCustomValidity(message);
                            field.reportValidity();
                            card?.classList.add('doc-attachment-card--error');

                            return false;
                        }

                        field.setCustomValidity('');
                    }

                    if (!field.checkValidity()) {
                        field.reportValidity();

                        return false;
                    }
                }

                return true;
            },

            prepareSubmit(event) {
                const form = event.target;
                if (!form) {
                    return;
                }

                form.querySelectorAll('[data-loan-scope="group"] input, [data-loan-scope="group"] select, [data-loan-scope="group"] textarea').forEach((field) => {
                    field.disabled = this.loanType !== 'group';
                });

                const stepInput = form.querySelector('input[name="step"]');
                if (stepInput) {
                    stepInput.value = this.step;
                }
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
