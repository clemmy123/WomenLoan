const FLASH_DISMISS_MS = 5000;

function dismissFlashElement(el) {
    if (!el || el.dataset.flashDismissing) {
        return;
    }

    el.dataset.flashDismissing = 'true';

    const reduceMotion = document.documentElement.classList.contains('a11y-reduce-motion');
    const duration = reduceMotion ? 0 : 400;

    el.classList.add('app-flash-dismissing');

    window.setTimeout(() => {
        el.remove();
    }, duration + 50);
}

function initAutoDismissFlash() {
    document.querySelectorAll('[data-auto-dismiss]').forEach((el) => {
        window.setTimeout(() => dismissFlashElement(el), FLASH_DISMISS_MS);
    });
}

document.addEventListener('DOMContentLoaded', initAutoDismissFlash);
