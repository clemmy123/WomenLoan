const STORAGE = {
    theme: 'theme',
    fontSize: 'a11y-font-size',
    highContrast: 'a11y-high-contrast',
    reduceMotion: 'a11y-reduce-motion',
};

function applyAccessibilityPreferences(state) {
    const root = document.documentElement;

    root.classList.toggle('dark', state.dark);
    root.classList.remove('a11y-text-large', 'a11y-text-xl');

    if (state.fontSize === 'large') {
        root.classList.add('a11y-text-large');
    } else if (state.fontSize === 'xl') {
        root.classList.add('a11y-text-xl');
    }

    root.classList.toggle('a11y-high-contrast', state.highContrast);
    root.classList.toggle('a11y-reduce-motion', state.reduceMotion);
}

function readAccessibilityState() {
    return {
        dark: localStorage.getItem(STORAGE.theme) === 'dark',
        fontSize: localStorage.getItem(STORAGE.fontSize) || 'normal',
        highContrast: localStorage.getItem(STORAGE.highContrast) === 'true',
        reduceMotion: localStorage.getItem(STORAGE.reduceMotion) === 'true',
    };
}

function persistAccessibilityState(state) {
    localStorage.setItem(STORAGE.theme, state.dark ? 'dark' : 'light');
    localStorage.setItem(STORAGE.fontSize, state.fontSize);
    localStorage.setItem(STORAGE.highContrast, state.highContrast ? 'true' : 'false');
    localStorage.setItem(STORAGE.reduceMotion, state.reduceMotion ? 'true' : 'false');
    applyAccessibilityPreferences(state);
    syncAccessibilityControls(state);
}

function syncAccessibilityControls(state) {
    document.querySelectorAll('[data-a11y-dark]').forEach((el) => {
        el.classList.toggle('is-active', String(state.dark) === el.dataset.a11yDark);
    });
    document.querySelectorAll('[data-a11y-font]').forEach((el) => {
        el.classList.toggle('is-active', el.dataset.a11yFont === state.fontSize);
    });
    document.querySelectorAll('[data-a11y-contrast]').forEach((el) => {
        el.classList.toggle('is-on', state.highContrast);
        el.setAttribute('aria-checked', state.highContrast ? 'true' : 'false');
    });
    document.querySelectorAll('[data-a11y-motion]').forEach((el) => {
        el.classList.toggle('is-on', state.reduceMotion);
        el.setAttribute('aria-checked', state.reduceMotion ? 'true' : 'false');
    });
}

function bindAccessibilityControls() {
    let state = readAccessibilityState();
    applyAccessibilityPreferences(state);
    syncAccessibilityControls(state);

    document.querySelectorAll('[data-a11y-dark]').forEach((el) => {
        el.addEventListener('click', () => {
            state = { ...state, dark: el.dataset.a11yDark === 'true' };
            persistAccessibilityState(state);
        });
    });

    document.querySelectorAll('[data-a11y-font]').forEach((el) => {
        el.addEventListener('click', () => {
            state = { ...state, fontSize: el.dataset.a11yFont };
            persistAccessibilityState(state);
        });
    });

    document.querySelectorAll('[data-a11y-contrast]').forEach((el) => {
        el.addEventListener('click', () => {
            state = { ...state, highContrast: ! state.highContrast };
            persistAccessibilityState(state);
        });
    });

    document.querySelectorAll('[data-a11y-motion]').forEach((el) => {
        el.addEventListener('click', () => {
            state = { ...state, reduceMotion: ! state.reduceMotion };
            persistAccessibilityState(state);
        });
    });
}

document.addEventListener('DOMContentLoaded', bindAccessibilityControls);

export { applyAccessibilityPreferences, readAccessibilityState };
