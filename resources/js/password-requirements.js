const PASSWORD_RULES = {
    length: (value) => value.length >= 8,
    letter: (value) => /[a-zA-Z]/.test(value),
    uppercase: (value) => /[A-Z]/.test(value),
    number: (value) => /\d/.test(value),
    symbol: (value) => /[^a-zA-Z0-9]/.test(value),
};

function updatePasswordRequirements(panel) {
    const targetId = panel.dataset.passwordTarget;
    const input = targetId ? document.getElementById(targetId) : null;

    if (!input) {
        return;
    }

    const items = panel.querySelectorAll('[data-rule]');
    const strongLabel = panel.querySelector('[data-strong-label]');
    const value = input.value;
    let allMet = true;

    items.forEach((item) => {
        const rule = item.dataset.rule;
        const met = PASSWORD_RULES[rule]?.(value) ?? false;

        item.classList.toggle('password-requirements-met', met);
        item.classList.toggle('password-requirements-unmet', value.length > 0 && !met);

        if (!met) {
            allMet = false;
        }
    });

    const isStrong = value.length > 0 && allMet;

    panel.classList.toggle('password-requirements--valid', isStrong);

    if (strongLabel) {
        strongLabel.hidden = !isStrong;
    }
}

function initPasswordRequirements() {
    document.querySelectorAll('[data-password-requirements]').forEach((panel) => {
        const targetId = panel.dataset.passwordTarget;
        const input = targetId ? document.getElementById(targetId) : null;

        if (!input) {
            return;
        }

        input.addEventListener('input', () => updatePasswordRequirements(panel));
        updatePasswordRequirements(panel);
    });
}

document.addEventListener('DOMContentLoaded', initPasswordRequirements);
