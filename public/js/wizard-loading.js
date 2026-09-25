const busyCounts = new WeakMap();

function loadingLabel() {
    return document.documentElement.dataset.loadingText || 'Loading…';
}

function overlay(root) {
    return root?.querySelector('[data-wizard-loading]') ?? null;
}

function applyBusy(root, busy) {
    const el = overlay(root);

    if (el) {
        el.hidden = ! busy;
    }

    root?.classList.toggle('is-wizard-busy', busy);
    root?.querySelector('form')?.setAttribute('aria-busy', busy ? 'true' : 'false');

    root?.querySelectorAll('[data-wizard-next], [data-wizard-back], [data-onboarding-next], [data-onboarding-back]').forEach((button) => {
        if (busy) {
            button.dataset.wizardWasDisabled = button.disabled ? '1' : '0';
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        } else if (button.dataset.wizardWasDisabled !== '1') {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    });
}

export function wizardRootFrom(el) {
    return el?.closest?.('[data-loan-wizard], [data-applicant-onboarding-wizard]') ?? null;
}

export function beginWizardLoading(root) {
    if (! root) {
        return;
    }

    const next = (busyCounts.get(root) || 0) + 1;
    busyCounts.set(root, next);
    applyBusy(root, true);

    const text = overlay(root)?.querySelector('[data-wizard-loading-text]');

    if (text && ! text.textContent.trim()) {
        text.textContent = loadingLabel();
    }
}

export function endWizardLoading(root) {
    if (! root) {
        return;
    }

    const next = Math.max(0, (busyCounts.get(root) || 0) - 1);
    busyCounts.set(root, next);

    if (next === 0) {
        applyBusy(root, false);
    }
}

export async function withWizardLoading(root, task, minMs = 280) {
    const started = Date.now();
    beginWizardLoading(root);

    try {
        return await task();
    } finally {
        const wait = minMs - (Date.now() - started);

        if (wait > 0) {
            await new Promise((resolve) => window.setTimeout(resolve, wait));
        }

        endWizardLoading(root);
    }
}
