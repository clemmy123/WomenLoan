function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        if (button.dataset.bound === 'true') {
            return;
        }

        button.dataset.bound = 'true';

        button.addEventListener('click', () => {
            const wrap = button.closest('.auth-split-input-wrap, .app-input-wrap, [data-password-wrap]');
            const input = wrap?.querySelector('input[type="password"], input[type="text"]');

            if (! input) {
                return;
            }

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? (button.dataset.hideLabel || '') : (button.dataset.showLabel || ''));
            button.querySelector('[data-password-icon="show"]')?.classList.toggle('d-none', show);
            button.querySelector('[data-password-icon="hide"]')?.classList.toggle('d-none', ! show);
        });
    });
}

function syncFiltersToggle(button) {
    const target = document.querySelector(button.getAttribute('data-bs-target'));
    const state = button.querySelector('.list-filters-toggle-state');
    const expanded = target?.classList.contains('show') || button.getAttribute('aria-expanded') === 'true';

    if (state) {
        state.textContent = expanded ? (button.dataset.hideLabel || '') : (button.dataset.showLabel || '');
    }
}

function initFiltersToggles() {
    document.querySelectorAll('.list-filters-toggle[data-bs-toggle="collapse"]').forEach((button) => {
        const target = document.querySelector(button.getAttribute('data-bs-target'));

        if (! target) {
            return;
        }

        syncFiltersToggle(button);
        target.addEventListener('shown.bs.collapse', () => syncFiltersToggle(button));
        target.addEventListener('hidden.bs.collapse', () => syncFiltersToggle(button));
    });
}

function initAutoSubmit() {
    document.querySelectorAll('[data-auto-submit-form]').forEach((input) => {
        if (input.dataset.bound === 'true') {
            return;
        }

        input.dataset.bound = 'true';
        const form = input.closest('form');
        const delay = Number(input.dataset.autoSubmitDelay || 350);
        let timer = null;

        const submit = () => {
            form?.requestSubmit();
        };

        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(submit, delay);
        });

        if (input.tagName === 'SELECT') {
            input.addEventListener('change', submit);
        }
    });
}

function initLandingHeader() {
    const header = document.querySelector('[data-landing-header]');
    const spacer = document.querySelector('[data-landing-spacer]');

    if (! header) {
        return;
    }

    const apply = () => {
        const floating = window.scrollY > 64;
        header.classList.toggle('is-floating', floating);

        if (spacer) {
            spacer.hidden = ! floating;
            spacer.style.height = floating ? `${header.offsetHeight}px` : '';
        }
    };

    apply();
    window.addEventListener('scroll', apply, { passive: true });
    window.addEventListener('resize', apply, { passive: true });
}

function initAccountActiveToggle() {
    document.querySelectorAll('[data-account-active]').forEach((root) => {
        const checkbox = root.querySelector('[data-account-active-input]');
        const reason = root.querySelector('[data-account-active-reason]');

        if (! checkbox || ! reason) {
            return;
        }

        const sync = () => {
            reason.hidden = checkbox.checked;
            const field = reason.querySelector('textarea, input');

            if (field) {
                field.required = ! checkbox.checked;
            }
        };

        checkbox.addEventListener('change', sync);
        sync();
    });
}

const AppModal = {
    open(el, trigger) {
        if (! el) {
            return;
        }

        if (el.parentElement !== document.body) {
            document.body.appendChild(el);
        }

        el.hidden = false;
        el.classList.add('is-open');
        el.setAttribute('aria-hidden', 'false');
        document.body.classList.add('app-modal-open');
        el.dispatchEvent(new CustomEvent('app-modal:open', { bubbles: true, detail: { trigger } }));
    },
    close(el) {
        if (! el) {
            return;
        }

        el.classList.remove('is-open');
        el.hidden = true;
        el.setAttribute('aria-hidden', 'true');

        if (! document.querySelector('.app-modal-root.is-open')) {
            document.body.classList.remove('app-modal-open');
        }

        el.dispatchEvent(new CustomEvent('app-modal:close', { bubbles: true }));
    },
};

window.AppModal = AppModal;

function initAppModals() {
    document.addEventListener('click', (event) => {
        const openBtn = event.target.closest('[data-open-modal], [data-bs-toggle="modal"]');

        if (openBtn && ! openBtn.closest('.app-modal-root')) {
            const id = openBtn.getAttribute('data-open-modal')
                || (openBtn.getAttribute('data-bs-target') || '').replace(/^#/, '');

            if (id) {
                event.preventDefault();
                AppModal.open(document.getElementById(id), openBtn);
            }

            return;
        }

        const closeBtn = event.target.closest('[data-close-modal], [data-bs-dismiss="modal"]');

        if (closeBtn) {
            event.preventDefault();
            AppModal.close(closeBtn.closest('.app-modal-root'));
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const open = [...document.querySelectorAll('.app-modal-root.is-open')].at(-1);
        AppModal.close(open);
    });
}

function initUserDeactivateModal() {
    const modal = document.getElementById('app-modal-deactivate');

    if (! modal) {
        return;
    }

    modal.addEventListener('app-modal:open', (event) => {
        const trigger = event.detail?.trigger;
        const form = modal.querySelector('[data-deactivate-form]');
        const name = modal.querySelector('[data-deactivate-name]');

        if (trigger?.dataset.userUrl && form) {
            form.action = trigger.dataset.userUrl;
        }
        if (trigger?.dataset.userName && name) {
            name.textContent = trigger.dataset.userName;
        }
    });

    if (document.querySelector('.is-deactivate-open')) {
        AppModal.open(modal);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initAppModals();
    initPasswordToggles();
    initFiltersToggles();
    initAutoSubmit();
    initLandingHeader();
    initAccountActiveToggle();
    initUserDeactivateModal();
});
