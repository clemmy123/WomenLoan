document.addEventListener('alpine:init', () => {
    Alpine.data('applicantOnboardingWizard', (config = {}) => ({
        step: config.initialStep ?? 1,
        totalSteps: 3,
        loanType: config.loanType ?? '',
        maritalStatus: config.maritalStatus ?? '',
        hasDisability: config.hasDisability ?? '',
        locationId: config.locationId ?? '',

        init() {
            const street = document.getElementById('street_select');

            if (street) {
                if (street.value) {
                    this.locationId = street.value;
                }

                street.addEventListener('change', () => {
                    this.locationId = street.value ?? '';
                });
            }

            ['region_select', 'district_select', 'council_select', 'ward_select'].forEach((id) => {
                const select = document.getElementById(id);

                if (select) {
                    select.addEventListener('change', () => {
                        this.locationId = '';
                    });
                }
            });
        },

        canAdvanceStep() {
            if (this.step === 1) {
                return this.loanType === 'individual' || this.loanType === 'group';
            }

            if (this.step === 2) {
                return this.maritalStatus !== '' && this.hasDisability !== '';
            }

            return false;
        },

        canSubmitProfile() {
            const street = document.getElementById('street_select');

            return this.locationId !== '' && street && !street.disabled;
        },

        next() {
            if (!this.canAdvanceStep()) {
                return;
            }

            if (this.step < this.totalSteps) {
                this.step += 1;
                this.scrollToWizard();

                if (this.step === 2) {
                    requestAnimationFrame(() => {
                        window.AppSelect?.refreshAppSelect(document.getElementById('marital_status'));
                        window.AppSelect?.refreshAppSelect(document.getElementById('has_disability'));
                    });
                }

                if (this.step === 3) {
                    requestAnimationFrame(() => this.refreshGeoSelects());
                }
            }
        },

        prev() {
            if (this.step > 1) {
                this.step -= 1;
                this.scrollToWizard();
            }
        },

        scrollToWizard() {
            this.$refs.wizardHeading?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        refreshGeoSelects() {
            ['region_select', 'district_select', 'council_select', 'ward_select', 'street_select'].forEach((id) => {
                const select = document.getElementById(id);

                if (select) {
                    window.AppSelect?.refreshAppSelect(select);
                }
            });
        },

        onSubmit(event) {
            if (!this.canSubmitProfile()) {
                event.preventDefault();
                this.step = 3;
                this.scrollToWizard();
            }
        },
    }));
});
