import { withWizardLoading } from '../wizard-loading.js?v=wdf20260925a';

function syncWizardStepper(stepper, step) {
    if (! stepper) {
        return;
    }

    stepper.querySelectorAll('[data-stepper-node]').forEach((node) => {
        const number = Number(node.dataset.stepperNode);
        const item = node.closest('.loan-wizard-stepper__item');

        node.classList.remove('is-active', 'is-complete', 'is-pending');
        if (step > number) {
            node.classList.add('is-complete');
        } else if (step === number) {
            node.classList.add('is-active');
            node.setAttribute('aria-current', 'step');
        } else {
            node.classList.add('is-pending');
            node.removeAttribute('aria-current');
        }

        item?.querySelectorAll('.loan-wizard-stepper__line').forEach((line) => {
            const lineNumber = Number(line.dataset.stepperLine);
            line.classList.remove('is-complete', 'is-active');
            if (step > lineNumber) {
                line.classList.add('is-complete');
            } else if (step === lineNumber) {
                line.classList.add('is-active');
            }
        });

        const title = item?.querySelector('.loan-wizard-stepper__title');
        if (title) {
            title.classList.remove('is-active', 'is-complete', 'is-pending');
            if (step > number) {
                title.classList.add('is-complete');
            } else if (step === number) {
                title.classList.add('is-active');
            } else {
                title.classList.add('is-pending');
            }
        }

        const check = item?.querySelector('.loan-wizard-stepper__check');
        if (check) {
            check.hidden = step <= number;
        }
    });
}

function initApplicantOnboardingWizard(root) {
    if (! root || root.dataset.bound === 'true') {
        return;
    }

    let config = {};
    try {
        config = JSON.parse(root.dataset.onboardingConfig || '{}');
    } catch {
        config = {};
    }

    const totalSteps = 3;
    let step = config.initialStep ?? 1;
    let loanType = config.loanType ?? '';
    let maritalStatus = config.maritalStatus ?? '';
    let hasDisability = config.hasDisability ?? '';
    let locationId = config.locationId ?? '';

    const form = root.querySelector('form');
    const heading = root.querySelector('[data-onboarding-heading]');
    const stepper = root.querySelector('.loan-wizard-stepper');
    const btnBack = root.querySelector('[data-onboarding-back]');
    const btnNext = root.querySelector('[data-onboarding-next]');
    const btnSubmit = root.querySelector('[data-onboarding-submit]');
    const loanTypeInputs = root.querySelectorAll('input[name="preferred_loan_type"]');
    const maritalSelect = root.querySelector('#marital_status');
    const disabilitySelect = root.querySelector('#has_disability');

    const readLocationId = () => {
        const street = document.getElementById('street_select');
        return street?.value ?? '';
    };

    const canAdvanceStep = () => {
        if (step === 1) {
            return loanType === 'individual' || loanType === 'group';
        }

        if (step === 2) {
            return maritalStatus !== '' && hasDisability !== '';
        }

        return false;
    };

    const canSubmitProfile = () => {
        const street = document.getElementById('street_select');

        return locationId !== '' && street && ! street.disabled;
    };

    const refreshGeoSelects = () => {
        ['region_select', 'district_select', 'council_select', 'ward_select', 'street_select'].forEach((id) => {
            const select = document.getElementById(id);
            if (select) {
                window.AppSelect?.refreshAppSelect(select);
            }
        });
    };

    const scrollToWizard = () => {
        heading?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const syncNavButtons = () => {
        if (btnBack) {
            btnBack.hidden = step <= 1;
        }

        if (btnNext) {
            btnNext.hidden = step >= totalSteps;
            btnNext.disabled = ! canAdvanceStep();
            btnNext.classList.toggle('opacity-40', ! canAdvanceStep());
            btnNext.classList.toggle('cursor-not-allowed', ! canAdvanceStep());
            btnNext.classList.toggle('cursor-pointer', canAdvanceStep());
        }

        if (btnSubmit) {
            btnSubmit.hidden = step !== totalSteps;
            const ok = canSubmitProfile();
            btnSubmit.disabled = ! ok;
            btnSubmit.classList.toggle('opacity-40', ! ok);
            btnSubmit.classList.toggle('cursor-not-allowed', ! ok);
            btnSubmit.classList.toggle('cursor-pointer', ok);
        }
    };

    const showStep = (nextStep) => {
        step = nextStep;
        root.querySelectorAll('[data-onboarding-step]').forEach((panel) => {
            const panelStep = Number(panel.dataset.onboardingStep);
            const active = panelStep === step;
            panel.hidden = ! active;
            panel.classList.toggle('wizard-step-active', active);
            panel.classList.toggle('wizard-step-inactive', ! active);
        });

        syncWizardStepper(stepper, step);
        syncNavButtons();
    };

    const next = () => {
        if (! canAdvanceStep() || step >= totalSteps) {
            return;
        }

        void withWizardLoading(root, async () => {
            showStep(step + 1);
            scrollToWizard();

            if (step === 2) {
                window.AppSelect?.refreshAppSelect(maritalSelect);
                window.AppSelect?.refreshAppSelect(disabilitySelect);
            }

            if (step === 3) {
                refreshGeoSelects();
            }
        });
    };

    const prev = () => {
        if (step <= 1) {
            return;
        }

        void withWizardLoading(root, async () => {
            showStep(step - 1);
            scrollToWizard();
        });
    };

    loanTypeInputs.forEach((input) => {
        input.addEventListener('change', () => {
            if (input.checked) {
                loanType = input.value;
                syncNavButtons();
            }
        });
        if (input.checked) {
            loanType = input.value;
        }
    });

    maritalSelect?.addEventListener('change', () => {
        maritalStatus = maritalSelect.value ?? '';
        syncNavButtons();
    });

    disabilitySelect?.addEventListener('change', () => {
        hasDisability = disabilitySelect.value ?? '';
        syncNavButtons();
    });

    const street = document.getElementById('street_select');
    if (street) {
        if (street.value) {
            locationId = street.value;
        }

        street.addEventListener('change', () => {
            locationId = street.value ?? '';
            syncNavButtons();
        });
    }

    ['region_select', 'district_select', 'council_select', 'ward_select'].forEach((id) => {
        const select = document.getElementById(id);
        select?.addEventListener('change', () => {
            locationId = '';
            syncNavButtons();
        });
    });

    btnBack?.addEventListener('click', prev);
    btnNext?.addEventListener('click', next);

    form?.addEventListener('submit', (event) => {
        locationId = readLocationId();
        if (! canSubmitProfile()) {
            event.preventDefault();
            showStep(3);
            scrollToWizard();
        }
    });

    root.dataset.bound = 'true';
    showStep(step);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-applicant-onboarding-wizard]').forEach(initApplicantOnboardingWizard);
});

export { syncWizardStepper };
