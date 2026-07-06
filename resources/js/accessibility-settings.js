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
}

function registerAccessibilityStore(Alpine) {
    Alpine.store('a11y', {
        ...readAccessibilityState(),

        init() {
            applyAccessibilityPreferences(this);
        },

        setDark(value) {
            this.dark = value;
            persistAccessibilityState(this);
        },

        toggleDark() {
            this.setDark(!this.dark);
        },

        setFontSize(size) {
            this.fontSize = size;
            persistAccessibilityState(this);
        },

        toggleHighContrast() {
            this.highContrast = !this.highContrast;
            persistAccessibilityState(this);
        },

        toggleReduceMotion() {
            this.reduceMotion = !this.reduceMotion;
            persistAccessibilityState(this);
        },
    });
}

document.addEventListener('alpine:init', () => {
    registerAccessibilityStore(window.Alpine);
    window.Alpine.store('a11y').init();
});

export { applyAccessibilityPreferences, readAccessibilityState, registerAccessibilityStore };
