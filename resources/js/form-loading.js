const SPINNER = `<svg class="animate-spin h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>`;

function loadingText() {
    return document.documentElement.dataset.loadingText || 'Loading…';
}

function submitButtons(form) {
    return Array.from(
        form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]')
    ).filter((btn) => btn.type === 'submit' || (btn.tagName === 'BUTTON' && !btn.type));
}

function isCompactButton(button) {
    return (
        button.classList.contains('text-xs')
        || button.classList.contains('text-red-600')
        || button.classList.contains('text-red-900')
        || button.dataset.loadingCompact === 'true'
    );
}

function setButtonLoading(button) {
    if (button.disabled || button.dataset.loadingActive === 'true') {
        return;
    }

    button.dataset.loadingActive = 'true';
    button.dataset.loadingOriginal = button.innerHTML;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.classList.add('opacity-70', 'cursor-wait', 'pointer-events-none');

    const label = button.dataset.loadingText || loadingText();

    if (isCompactButton(button)) {
        button.innerHTML = `<span class="inline-flex items-center justify-center">${SPINNER}</span>`;
        return;
    }

    button.innerHTML = `<span class="inline-flex items-center justify-center gap-2">${SPINNER}<span>${label}</span></span>`;
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset.noLoading === 'true') {
        return;
    }

    const submitter = event.submitter;
    const buttons = submitter ? [submitter] : submitButtons(form);

    buttons.forEach(setButtonLoading);

    if (submitter) {
        submitButtons(form).forEach((btn) => {
            if (btn !== submitter) {
                btn.disabled = true;
            }
        });
    }
});
