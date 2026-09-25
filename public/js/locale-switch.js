const SCROLL_KEY = 'localeScrollY';

function currentRedirectPath() {
    const url = new URL(window.location.href);

    const wizardRoot = document.querySelector('[data-loan-wizard]');
    const stepInput = wizardRoot?.querySelector('input[name="step"]');

    if (stepInput?.value) {
        url.searchParams.set('wizard_step', String(stepInput.value));
    }

    return url.pathname + url.search + url.hash;
}

function bindLocaleLinks() {
    document.querySelectorAll('[data-locale-switch]').forEach((link) => {
        if (link.dataset.localeBound === 'true') {
            return;
        }

        link.dataset.localeBound = 'true';

        link.addEventListener('click', (event) => {
            event.preventDefault();

            sessionStorage.setItem(SCROLL_KEY, String(window.scrollY));

            const switchUrl = new URL(link.href, window.location.origin);
            switchUrl.searchParams.set('redirect', currentRedirectPath());

            window.location.assign(switchUrl.toString());
        });
    });
}

function restoreLocaleScroll() {
    const stored = sessionStorage.getItem(SCROLL_KEY);

    if (stored === null) {
        return;
    }

    sessionStorage.removeItem(SCROLL_KEY);

    const y = Number.parseInt(stored, 10);

    if (Number.isNaN(y)) {
        return;
    }

    const restore = () => window.scrollTo(0, y);

    restore();
    requestAnimationFrame(restore);
    window.addEventListener('load', restore, { once: true });
}

document.addEventListener('DOMContentLoaded', () => {
    restoreLocaleScroll();
    bindLocaleLinks();
});
