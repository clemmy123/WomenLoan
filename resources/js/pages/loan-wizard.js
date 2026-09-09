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
            totalSteps: config.totalSteps ?? 6,
            editing: config.editing ?? false,
            isDraft: config.isDraft ?? false,
            submitModal: false,
            preview: { documents: [] },
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
            declarationAccepted: Boolean(config.declarationAccepted),
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
            draftSaveUrl: config.draftSaveUrl ?? '',
            draftSaveTimer: null,
            draftSaving: false,

            get stepText() {
                return stepLabel(this.i18n.step ?? 'Step :step / :total', this.step, this.totalSteps);
            },

            get canAdvanceStep() {
                void this.formValidityTick;

                if (this.step >= this.totalSteps) {
                    return false;
                }

                return this.isStepComplete(this.step);
            },

            formValidityTick: 0,

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

                const form = this.$root.querySelector('form');

                if (form) {
                    const bumpValidity = () => {
                        this.refreshStepValidity();
                        this.autoSaveDraft();
                    };

                    form.addEventListener('input', bumpValidity, { passive: true });
                    form.addEventListener('change', bumpValidity, { passive: true });
                }

                this.$watch('step', () => this.refreshStepValidity());
                this.$watch('loanType', () => this.refreshStepValidity());
                this.$watch('declarationAccepted', () => this.refreshStepValidity());
                this.$watch('selectedBusinessSector', () => this.refreshStepValidity());
                this.$watch('selectedBusinessType', () => this.refreshStepValidity());
                this.$watch('selectedRegion', () => this.refreshStepValidity());
                this.$watch('selectedDistrict', () => this.refreshStepValidity());
                this.$watch('selectedCouncil', () => this.refreshStepValidity());
                this.$watch('selectedWard', () => this.refreshStepValidity());
                this.$watch('selectedStreet', () => this.refreshStepValidity());
                this.$watch('guarantorRegion', () => this.refreshStepValidity());
                this.$watch('guarantorDistrict', () => this.refreshStepValidity());
                this.$watch('guarantorCouncil', () => this.refreshStepValidity());
                this.$watch('guarantorWard', () => this.refreshStepValidity());
                this.$watch('guarantorStreet', () => this.refreshStepValidity());

                if (this.step === this.totalSteps) {
                    this.refreshPreview();
                }

                if (this.step > 1) {
                    queueMicrotask(() => this.scrollToActiveStep({ behavior: 'auto' }));
                }

                this.syncWizardUrl();
            },

            autoSaveDraft() {
                if (this.editing || !this.draftSaveUrl) {
                    return;
                }

                clearTimeout(this.draftSaveTimer);
                this.draftSaveTimer = window.setTimeout(() => {
                    this.persistDraft();
                }, 900);
            },

            syncWizardUrl() {
                if (this.editing) {
                    return;
                }

                const form = this.$root.querySelector('form');
                const trackInput = form?.querySelector('input[name="track_id"]');
                const trackId = trackInput?.value;

                if (!trackId) {
                    return;
                }

                const url = new URL(window.location.href);
                url.searchParams.set('resume_track_id', trackId);
                url.searchParams.set('wizard_step', String(this.step));
                window.history.replaceState({}, '', url);
            },

            async persistDraft() {
                if (this.editing || !this.draftSaveUrl) {
                    return;
                }

                const form = this.$root.querySelector('form');

                if (!form) {
                    return;
                }

                this.syncFormFieldsForSubmit(form);
                window.syncAmountFields?.(form);
                window.syncPhoneFields?.(form);

                const formData = new FormData(form);
                formData.set('step', String(this.step));

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                this.draftSaving = true;

                try {
                    const response = await fetch(this.draftSaveUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            Accept: 'application/json',
                            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    const trackInput = form.querySelector('input[name="track_id"]');

                    if (trackInput && payload?.track_id) {
                        trackInput.value = payload.track_id;
                    }

                    this.syncWizardUrl();
                } catch (e) {
                    console.error(e);
                } finally {
                    this.draftSaving = false;
                }
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
                    this.refreshStepValidity();

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

            refreshStepValidity() {
                this.formValidityTick += 1;
            },

            getStepPanel(step) {
                const form = this.$root.querySelector('form');

                return form?.querySelector(`[data-wizard-step="${step}"]`) ?? null;
            },

            isFieldMandatory(field) {
                return Boolean(field.required || field.dataset.docRequired === 'true');
            },

            isOptionalFieldEmpty(field) {
                if (field.type === 'checkbox') {
                    return !field.checked;
                }

                if (field.type === 'file') {
                    return !field.files?.[0] && field.dataset.hasExisting !== 'true';
                }

                return String(field.value ?? '').trim() === '';
            },

            mandatoryDocumentsForStep(step) {
                if (this.editing) {
                    return [];
                }

                if (step === 1) {
                    const docs = [
                        'business_proposal_document',
                        'proof_address_attachment',
                        'application_letter',
                        'bank_statement',
                    ];

                    if (this.loanType === 'group') {
                        docs.push('group_constitution', 'group_muhtasari', 'group_certificate');
                    }

                    return docs;
                }

                if (step === 2) {
                    return ['guarantor_letter'];
                }

                return [];
            },

            mandatoryDocumentsComplete(step) {
                return this.mandatoryDocumentsForStep(step).every((name) => this.documentAttached(name));
            },

            validateMandatoryDocuments(step, options = {}) {
                const { silent = false } = options;
                const form = this.$root.querySelector('form');

                if (!form) {
                    return true;
                }

                const missing = this.mandatoryDocumentsForStep(step).filter((name) => !this.documentAttached(name));

                if (missing.length === 0) {
                    return true;
                }

                const message = this.i18n.document_required ?? 'Please upload this document to continue.';

                for (const name of missing) {
                    const field = form.querySelector(`[name="${name}"]`);

                    if (!field) {
                        continue;
                    }

                    const card = field.closest('.doc-attachment-card--upload');

                    if (!silent) {
                        field.setCustomValidity(message);
                        card?.classList.add('doc-attachment-card--error');
                    }
                }

                if (!silent) {
                    const firstMissing = form.querySelector(`[name="${missing[0]}"]`);

                    if (firstMissing?.reportValidity) {
                        firstMissing.reportValidity();
                    }
                }

                return false;
            },

            isStepComplete(step) {
                const form = this.$root.querySelector('form');

                if (form) {
                    this.syncFormFieldsForSubmit(form);
                }

                const panel = this.getStepPanel(step);

                if (panel) {
                    window.syncPhoneFields?.(panel);
                    window.syncAmountFields?.(panel);
                }

                if (step === 1) {
                    const tin = this.formValue('tin_number');

                    if (
                        !this.selectedRegion
                        || !this.selectedDistrict
                        || !this.selectedCouncil
                        || !this.selectedWard
                        || !this.selectedStreet
                        || !this.formValue('business_sector')
                        || !this.formValue('business_type')
                        || !this.formValue('business_name')
                        || !this.formValue('business_phone')
                        || !window.isTinComplete?.(tin)
                        || !this.mandatoryDocumentsComplete(1)
                    ) {
                        return false;
                    }

                    return true;
                }

                if (step === 2) {
                    const nin = this.formValue('guarantor_nin');

                    if (
                        !this.formValue('guarantor_first_name')
                        || !this.formValue('guarantor_last_name')
                        || !this.formValue('guarantor_phone')
                        || !this.formValue('guarantor_sex')
                        || !window.isNinComplete?.(nin)
                        || !this.guarantorRegion
                        || !this.guarantorDistrict
                        || !this.guarantorCouncil
                        || !this.guarantorWard
                        || !this.guarantorStreet
                        || !this.mandatoryDocumentsComplete(2)
                    ) {
                        return false;
                    }

                    return true;
                }

                if (step === 3) {
                    return Boolean(this.formValue('requested_amount'));
                }

                if (step === 4) {
                    return true;
                }

                if (step === 5) {
                    return this.declarationAccepted;
                }

                return true;
            },

            validateStep(step, options = {}) {
                const { silent = false, mandatoryOnly = false } = options;
                const form = this.$root.querySelector('form');
                if (!form) {
                    return true;
                }

                this.syncFormFieldsForSubmit(form);

                const panel = form.querySelector(`[data-wizard-step="${step}"]`);
                if (!panel) {
                    return true;
                }

                window.syncPhoneFields?.(panel);
                window.syncAmountFields?.(panel);

                const maxBytes = 1024 * 1024;
                const fields = panel.querySelectorAll('input, select, textarea');

                for (const field of fields) {
                    const isMandatory = this.isFieldMandatory(field);

                    if (field.disabled && isMandatory) {
                        field.disabled = false;
                    }

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

                    if (mandatoryOnly && !isMandatory) {
                        continue;
                    }

                    if (!isMandatory && this.isOptionalFieldEmpty(field)) {
                        field.setCustomValidity('');

                        continue;
                    }

                    if (field.matches('[data-tin-input], [data-nin-input]')) {
                        if (!window.validateIdentityInput?.(field, { silent })) {
                            return false;
                        }

                        continue;
                    }

                    if (field.type === 'file') {
                        const card = field.closest('.doc-attachment-card--upload');
                        const file = field.files?.[0];
                        const hasExisting = field.dataset.hasExisting === 'true';
                        const mustUpload = isMandatory;

                        card?.classList.remove('doc-attachment-card--error');

                        if (mustUpload && !file && !hasExisting) {
                            const message = this.i18n.document_required ?? 'Please upload this document to continue.';

                            if (!silent) {
                                field.setCustomValidity(message);
                                field.reportValidity();
                                card?.classList.add('doc-attachment-card--error');
                            }

                            return false;
                        }

                        if (file && file.size > maxBytes) {
                            const message = this.i18n.file_too_large ?? 'File must not exceed 1MB.';

                            if (!silent) {
                                field.setCustomValidity(message);
                                field.reportValidity();
                                card?.classList.add('doc-attachment-card--error');
                            }

                            return false;
                        }

                        field.setCustomValidity('');
                    }

                    if (!field.checkValidity()) {
                        if (!silent) {
                            field.reportValidity();
                        }

                        return false;
                    }
                }

                if (step === 1 || step === 2) {
                    return this.validateMandatoryDocuments(step, { silent });
                }

                return true;
            },

            prepareSubmit(event) {
                const form = event.target;
                if (!form) {
                    return;
                }

                this.syncFormFieldsForSubmit(form);
                window.syncAmountFields?.(form);
                window.syncPhoneFields?.(form);

                form.querySelectorAll('[data-loan-scope="group"] input, [data-loan-scope="group"] select, [data-loan-scope="group"] textarea').forEach((field) => {
                    field.disabled = this.loanType !== 'group';
                });

                const stepInput = form.querySelector('input[name="step"]');
                if (stepInput) {
                    stepInput.value = this.step;
                }
            },

            syncFormFieldsForSubmit(form) {
                const catalogFields = {
                    business_sector: this.selectedBusinessSector,
                    business_type: this.selectedBusinessType,
                };

                Object.entries(catalogFields).forEach(([name, value]) => {
                    const field = form.querySelector(`[name="${name}"]`);

                    if (!field) {
                        return;
                    }

                    if (value !== '' && value != null) {
                        field.value = String(value);
                    }

                    if (name === 'business_type' && this.selectedBusinessSector) {
                        field.disabled = false;
                    }

                    if (name === 'business_sector') {
                        field.disabled = false;
                    }

                    window.AppSelect?.refreshAppSelect(field);
                });

                const geoFields = {
                    region_id: this.selectedRegion,
                    district_id: this.selectedDistrict,
                    council_id: this.selectedCouncil,
                    ward_id: this.selectedWard,
                    street_id: this.selectedStreet,
                    guarantor_region_id: this.guarantorRegion,
                    guarantor_district_id: this.guarantorDistrict,
                    guarantor_council_id: this.guarantorCouncil,
                    guarantor_ward_id: this.guarantorWard,
                    guarantor_street_id: this.guarantorStreet,
                };

                Object.entries(geoFields).forEach(([name, value]) => {
                    const field = form.querySelector(`[name="${name}"]`);

                    if (!field) {
                        return;
                    }

                    if (value !== '' && value != null) {
                        field.value = String(value);
                    }

                    field.disabled = false;
                });
            },

            scrollToActiveStep(options = {}) {
                const { behavior = 'smooth' } = options;

                this.$nextTick(() => {
                    const panel = this.getStepPanel(this.step);
                    const heading = panel?.querySelector('.wizard-step-heading');

                    if (!heading) {
                        return;
                    }

                    heading.scrollIntoView({
                        behavior,
                        block: 'start',
                    });
                });
            },

            scrollToFirstFieldError() {
                this.$nextTick(() => {
                    const panel = this.getStepPanel(this.step);

                    if (!panel) {
                        return;
                    }

                    const firstInvalid = panel.querySelector('.app-identity-invalid')
                        ?? panel.querySelector('.doc-attachment-card--error input[type="file"]')
                        ?? this.mandatoryDocumentsForStep(this.step)
                            .map((name) => panel.querySelector(`[name="${name}"]`))
                            .find((field) => field && !this.documentAttached(field.name))
                        ?? panel.querySelector(':invalid');

                    if (!firstInvalid) {
                        return;
                    }

                    const focusTarget = firstInvalid.matches('input, select, textarea')
                        ? firstInvalid
                        : firstInvalid.querySelector('input, select, textarea') ?? firstInvalid;

                    focusTarget.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });

                    if (typeof focusTarget.focus === 'function') {
                        focusTarget.focus({ preventScroll: true });
                    }
                });
            },

            prevStep() {
                if (this.step <= 1) {
                    return;
                }

                this.step--;
                this.scrollToActiveStep();
            },

            async nextStep() {
                if (!this.canAdvanceStep) {
                    this.validateStep(this.step, { mandatoryOnly: true });
                    this.scrollToFirstFieldError();

                    return;
                }

                if (this.step < this.totalSteps) {
                    await this.persistDraft();
                    this.step++;
                    this.syncWizardUrl();
                    this.scrollToActiveStep();

                    if (this.step === this.totalSteps) {
                        this.refreshPreview();
                    }

                    await this.persistDraft();
                }
            },

            formValue(name) {
                const form = this.$root.querySelector('form');
                const field = form?.querySelector(`[name="${name}"]`);

                if (!field) {
                    return '';
                }

                if (field.type === 'checkbox') {
                    return field.checked ? field.value : '';
                }

                return String(field.value ?? '').trim();
            },

            selectLabel(name) {
                const form = this.$root.querySelector('form');
                const field = form?.querySelector(`[name="${name}"]`);

                if (!field || field.tagName !== 'SELECT') {
                    return this.formValue(name);
                }

                const option = field.options[field.selectedIndex];

                return option?.value ? String(option.text).trim() : '';
            },

            documentAttached(name) {
                const form = this.$root.querySelector('form');
                const field = form?.querySelector(`[name="${name}"]`);

                if (!field) {
                    return false;
                }

                return Boolean(field.files?.[0]) || field.dataset.hasExisting === 'true';
            },

            formatPreviewAmount(value) {
                const amount = Number(String(value).replace(/[^\d.]/g, ''));

                if (!Number.isFinite(amount) || amount <= 0) {
                    return '';
                }

                return `TZS ${amount.toLocaleString()}`;
            },

            geoLabel(collection, selectedId, fieldName) {
                const fromSelect = this.selectLabel(fieldName);

                if (fromSelect && !fromSelect.startsWith('--')) {
                    return fromSelect;
                }

                const item = this[collection]?.find((entry) => String(entry.id) === String(selectedId));

                return item?.name ?? '';
            },

            joinLocation(parts) {
                return parts.filter((part) => part && part.trim() !== '').join(' → ');
            },

            yesNoLabel(value) {
                if (value === '1' || value === 1 || value === true) {
                    return this.i18n.yes ?? 'Yes';
                }

                if (value === '0' || value === 0 || value === false) {
                    return this.i18n.no ?? 'No';
                }

                return '';
            },

            refreshPreview() {
                const region = this.geoLabel('regions', this.selectedRegion, 'region_id');
                const district = this.geoLabel('districts', this.selectedDistrict, 'district_id');
                const council = this.geoLabel('councils', this.selectedCouncil, 'council_id');
                const ward = this.geoLabel('wards', this.selectedWard, 'ward_id');
                const street = this.geoLabel('streets', this.selectedStreet, 'street_id');

                const guarantorRegion = this.geoLabel('guarantorRegions', this.guarantorRegion, 'guarantor_region_id')
                    || this.geoLabel('regions', this.guarantorRegion, 'guarantor_region_id');
                const guarantorDistrict = this.geoLabel('guarantorDistricts', this.guarantorDistrict, 'guarantor_district_id');
                const guarantorCouncil = this.geoLabel('guarantorCouncils', this.guarantorCouncil, 'guarantor_council_id');
                const guarantorWard = this.geoLabel('guarantorWards', this.guarantorWard, 'guarantor_ward_id');
                const guarantorStreet = this.geoLabel('guarantorStreets', this.guarantorStreet, 'guarantor_street_id');

                const middleName = this.formValue('guarantor_middle_name');

                this.preview = {
                    loan_type_label: this.loanType === 'group'
                        ? (this.i18n.loan_type_group ?? 'Group')
                        : (this.i18n.loan_type_individual ?? 'Individual'),
                    status_label: this.isDraft
                        ? (this.i18n.preview_status_draft ?? 'Draft')
                        : (this.i18n.preview_status_pending ?? 'Pending'),
                    business_name: this.formValue('business_name'),
                    business_phone: this.formValue('business_phone'),
                    business_email: this.formValue('business_email'),
                    business_sector: this.selectLabel('business_sector'),
                    business_type: this.selectLabel('business_type'),
                    tin_number: this.formValue('tin_number'),
                    business_location: this.joinLocation([region, district, council, ward, street]),
                    guarantor_first_name: this.formValue('guarantor_first_name'),
                    guarantor_middle_name: middleName || (this.i18n.no ?? '—'),
                    guarantor_last_name: this.formValue('guarantor_last_name'),
                    guarantor_phone: this.formValue('guarantor_phone'),
                    guarantor_nin: this.formValue('guarantor_nin'),
                    guarantor_relationship: this.loanType === 'group'
                        ? (this.i18n.guarantor ?? 'Guarantor')
                        : this.selectLabel('guarantor_relationship'),
                    guarantor_occupation: this.formValue('guarantor_occupation'),
                    guarantor_sex: this.selectLabel('guarantor_sex'),
                    guarantor_location: this.joinLocation([
                        guarantorRegion,
                        guarantorDistrict,
                        guarantorCouncil,
                        guarantorWard,
                        guarantorStreet,
                    ]),
                    requested_amount: this.formatPreviewAmount(this.formValue('requested_amount')),
                    has_disability: this.yesNoLabel(this.formValue('has_disability')),
                    is_widowed: this.yesNoLabel(this.formValue('is_widowed')),
                    bank_name: this.selectLabel('bank_name'),
                    bank_number: this.formValue('bank_number'),
                    declaration: this.formValue('declaration') === '1'
                        ? (this.i18n.declaration_confirmed ?? 'Confirmed')
                        : (this.i18n.no ?? 'No'),
                    documents: [
                        { label: this.i18n.business_proposal ?? 'Business Proposal', attached: this.documentAttached('business_proposal_document') },
                        { label: this.i18n.business_license ?? this.i18n.business_registration ?? 'Business License', attached: this.documentAttached('business_registration_attachment') },
                        { label: this.i18n.proof_address ?? 'Proof of Address', attached: this.documentAttached('proof_address_attachment') },
                        { label: this.i18n.application_letter ?? 'Application Letter', attached: this.documentAttached('application_letter') },
                        { label: this.i18n.bank_statement ?? 'Bank Statement', attached: this.documentAttached('bank_statement') },
                        { label: this.i18n.guarantor_letter ?? 'Guarantor Letter', attached: this.documentAttached('guarantor_letter') },
                    ],
                };

                if (this.loanType === 'group') {
                    this.preview.documents.push(
                        { label: this.i18n.group_constitution ?? 'Group Constitution', attached: this.documentAttached('group_constitution') },
                        { label: this.i18n.group_muhtasari ?? 'Group Summary', attached: this.documentAttached('group_muhtasari') },
                        { label: this.i18n.group_certificate ?? 'Group Certificate', attached: this.documentAttached('group_certificate') },
                    );
                }
            },

            validateAllSteps() {
                for (let current = 1; current <= 5; current += 1) {
                    if (!this.isStepComplete(current)) {
                        this.validateStep(current, { mandatoryOnly: true });
                        this.step = current;
                        this.scrollToActiveStep();
                        this.scrollToFirstFieldError();

                        return false;
                    }
                }

                return true;
            },

            openSubmitConfirm() {
                if (!this.declarationAccepted) {
                    this.step = 5;
                    this.scrollToActiveStep();

                    return;
                }

                const form = this.$root.querySelector('form');

                if (form) {
                    this.syncFormFieldsForSubmit(form);
                    window.syncAmountFields?.(form);
                    window.syncPhoneFields?.(form);
                }

                if (!this.validateAllSteps()) {
                    return;
                }

                this.refreshPreview();
                this.submitModal = true;
            },

            confirmSubmit() {
                this.submitModal = false;

                const form = this.$root.querySelector('form');
                const submitButton = this.$refs.finalSubmit;

                if (!form || !submitButton) {
                    return;
                }

                this.syncFormFieldsForSubmit(form);
                window.syncAmountFields?.(form);
                window.syncPhoneFields?.(form);

                let actionInput = form.querySelector('input[name="form_action"]');

                if (this.editing) {
                    if (!actionInput) {
                        actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'form_action';
                        form.appendChild(actionInput);
                    }

                    actionInput.value = 'submit_to_ward';
                } else if (actionInput) {
                    actionInput.remove();
                }

                form.requestSubmit(submitButton);
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
