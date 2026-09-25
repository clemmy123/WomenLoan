import '../accessibility-settings.js';
import '../locale-switch.js';
import '../ui-mvc.js';

function formatCounter(value) {
    return new Intl.NumberFormat(document.documentElement.lang || undefined).format(Math.round(value));
}

function animateCounter(el, target, duration = 1600) {
    const start = performance.now();
    const from = 0;

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - (1 - progress) ** 3;
        const current = from + (target - from) * eased;

        el.textContent = formatCounter(current);

        if (progress < 1) {
            requestAnimationFrame(tick);
        } else {
            el.textContent = formatCounter(target);
            el.dataset.counted = '1';
        }
    };

    requestAnimationFrame(tick);
}

function initLandingCounters() {
    const counters = document.querySelectorAll('[data-counter][data-target]');

    if (! counters.length) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const run = (el) => {
        if (el.dataset.counted === '1') {
            return;
        }

        const target = Number(el.dataset.target || 0);

        if (reduceMotion || ! Number.isFinite(target)) {
            el.textContent = formatCounter(target || 0);
            el.dataset.counted = '1';

            return;
        }

        animateCounter(el, target);
    };

    if (! ('IntersectionObserver' in window)) {
        counters.forEach(run);

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return;
                }

                run(entry.target);
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.35 }
    );

    counters.forEach((el) => observer.observe(el));
}

document.addEventListener('DOMContentLoaded', initLandingCounters);
