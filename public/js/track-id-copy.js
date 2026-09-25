function copyTrackId(button) {
    const value = button.dataset.copyTrackId;

    if (!value) {
        return;
    }

    const copiedLabel = button.dataset.copiedLabel ?? 'Copied';
    const valueEl = button.querySelector('.track-id-chip-value');

    navigator.clipboard.writeText(value).then(() => {
        button.classList.add('is-copied');

        if (valueEl) {
            valueEl.dataset.originalText = valueEl.textContent;
            valueEl.textContent = copiedLabel;
        }

        window.setTimeout(() => {
            button.classList.remove('is-copied');

            if (valueEl?.dataset.originalText) {
                valueEl.textContent = valueEl.dataset.originalText;
            }
        }, 1800);
    }).catch(() => {
        button.classList.add('is-copy-failed');
        window.setTimeout(() => button.classList.remove('is-copy-failed'), 1800);
    });
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy-track-id]');

    if (!button) {
        return;
    }

    event.preventDefault();
    copyTrackId(button);
});

export { copyTrackId };
