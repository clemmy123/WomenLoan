const FLASH_MIN_MS = 4000;
const FLASH_MAX_MS = 16000;
const FLASH_BASE_MS = 2500;
const FLASH_MS_PER_CHAR = 55;

function flashTextLength(el) {
    const text = (el.textContent || '').replace(/\s+/g, ' ').trim();

    return text.length;
}

function flashDismissDelay(el) {
    const chars = flashTextLength(el);
    const computed = FLASH_BASE_MS + chars * FLASH_MS_PER_CHAR;

    return Math.min(FLASH_MAX_MS, Math.max(FLASH_MIN_MS, computed));
}

function dismissFlashElement(el) {
    if (!el || el.dataset.flashDismissing) {
        return;
    }

    el.dataset.flashDismissing = 'true';

    const reduceMotion = document.documentElement.classList.contains('a11y-reduce-motion');
    const duration = reduceMotion ? 0 : 350;

    el.classList.add('app-flash-dismissing');

    window.setTimeout(() => {
        el.remove();
    }, duration + 40);
}

function scheduleFlashDismiss(el) {
    let remaining = flashDismissDelay(el);
    let startedAt = Date.now();
    let timerId = window.setTimeout(() => dismissFlashElement(el), remaining);

    const pause = () => {
        if (el.dataset.flashDismissing) {
            return;
        }

        window.clearTimeout(timerId);
        remaining = Math.max(1200, remaining - (Date.now() - startedAt));
    };

    const resume = () => {
        if (el.dataset.flashDismissing) {
            return;
        }

        startedAt = Date.now();
        timerId = window.setTimeout(() => dismissFlashElement(el), remaining);
    };

    el.addEventListener('mouseenter', pause);
    el.addEventListener('mouseleave', resume);
    el.addEventListener('focusin', pause);
    el.addEventListener('focusout', resume);
}

function initAutoDismissFlash() {
    document.querySelectorAll('[data-auto-dismiss]').forEach((el) => {
        scheduleFlashDismiss(el);
    });
}

document.addEventListener('DOMContentLoaded', initAutoDismissFlash);
