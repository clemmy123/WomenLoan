function parseDateOnly(value) {
    if (!value || typeof value !== 'string') {
        return null;
    }

    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
    if (!match) {
        return null;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    const date = new Date(year, month - 1, day);

    if (
        date.getFullYear() !== year
        || date.getMonth() !== month - 1
        || date.getDate() !== day
    ) {
        return null;
    }

    return date;
}

/**
 * Birthday-aware completed years.
 * Born 1995-07-07 is 30 on 2026-07-06 and 31 on 2026-07-07.
 */
export function calculateAge(dobValue, asOf = new Date()) {
    const birth = parseDateOnly(dobValue);
    if (!birth) {
        return null;
    }

    const today = new Date(asOf.getFullYear(), asOf.getMonth(), asOf.getDate());
    if (birth > today) {
        return null;
    }

    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age -= 1;
    }

    return age;
}

function bindAgeDisplay(input) {
    if (input.dataset.ageBound === '1') {
        return;
    }

    input.dataset.ageBound = '1';

    const targetId = input.dataset.ageDisplay;
    const target = targetId ? document.getElementById(targetId) : null;
    if (!target) {
        return;
    }

    const template = target.dataset.ageTemplate || ':age';
    const emptyText = target.dataset.ageEmpty || '';

    const sync = () => {
        const age = calculateAge(input.value);
        if (age === null) {
            target.textContent = emptyText;
            target.hidden = emptyText === '';
            return;
        }

        target.textContent = template.replace(':age', String(age));
        target.hidden = false;
    };

    input.addEventListener('input', sync);
    input.addEventListener('change', sync);
    sync();
}

window.calculateAge = calculateAge;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-age-display]').forEach((input) => {
        bindAgeDisplay(input);
    });
});
