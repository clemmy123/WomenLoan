const SCROLL_KEY = 'localeScrollY';

function currentRedirectPath() {
    const url = new URL(window.location.href);

    const wizardRoot = document.querySelector('[x-data*="loanWizard"]');

    if (wizardRoot && window.Alpine) {
        const data = window.Alpine.$data(wizardRoot);

        if (data?.step) {
            url.searchParams.set('wizard_step', String(data.step));
        }
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

document.addEventListener('alpine:initialized', bindLocaleLinks);
