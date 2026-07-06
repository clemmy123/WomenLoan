const MAX_FILE_BYTES = 1024 * 1024;

function isPdfFile(file) {
    const name = file.name.toLowerCase();

    return name.endsWith('.pdf') || file.type === 'application/pdf';
}

function pdfOnlyMessage(input) {
    return input.dataset.pdfOnlyMessage ?? 'Only PDF files are allowed.';
}

function fileTooLargeMessage(input) {
    const maxKb = Number.parseInt(input.dataset.maxKb ?? '1024', 10);

    return input.dataset.tooLargeMessage
        ?? `File must not exceed ${Math.round(maxKb / 1024)}MB.`;
}

function setFileFieldError(input, message) {
    const card = input.closest('.doc-attachment-card--upload');
    const errorEl = card?.querySelector('[data-doc-size-error]');

    input.setCustomValidity(message ?? '');

    if (message) {
        card?.classList.add('doc-attachment-card--error');

        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = false;
        }
    } else {
        card?.classList.remove('doc-attachment-card--error');

        if (errorEl) {
            errorEl.textContent = '';
            errorEl.hidden = true;
        }
    }
}

function updateUploadStatus(input, file = null) {
    const card = input.closest('.doc-attachment-card--upload');

    if (!card) {
        return;
    }

    const hintEl = card.querySelector('[data-doc-hint]');
    const uploadedEl = card.querySelector('[data-doc-uploaded]');
    const filenameEl = card.querySelector('[data-doc-filename]');
    const selectedFile = file ?? input.files?.[0] ?? null;
    const hasExisting = input.dataset.hasExisting === 'true' && !selectedFile;
    const isUploaded = Boolean(selectedFile) || hasExisting;

    card.classList.toggle('has-file', isUploaded);
    card.classList.toggle('is-uploaded', isUploaded);

    if (hintEl) {
        hintEl.hidden = isUploaded;
    }

    if (uploadedEl) {
        uploadedEl.hidden = !isUploaded;
    }

    if (filenameEl) {
        if (selectedFile) {
            filenameEl.textContent = selectedFile.name;
            filenameEl.hidden = false;
        } else if (hasExisting && input.dataset.existingName) {
            filenameEl.textContent = input.dataset.existingName;
            filenameEl.hidden = false;
        } else {
            filenameEl.textContent = '';
            filenameEl.hidden = true;
        }
    }
}

function isPdfUrl(url) {
    try {
        const pathname = new URL(url, window.location.origin).pathname;

        return pathname.toLowerCase().endsWith('.pdf');
    } catch {
        return url.toLowerCase().includes('.pdf');
    }
}

function getViewerElements() {
    let modal = document.getElementById('doc-viewer-modal');

    if (!modal) {
        return null;
    }

    return {
        modal,
        title: modal.querySelector('#doc-viewer-title'),
        frame: modal.querySelector('.doc-viewer-frame'),
        fallback: modal.querySelector('.doc-viewer-fallback'),
        fallbackLink: modal.querySelector('[data-doc-viewer-fallback-link]'),
        openTab: modal.querySelector('[data-doc-viewer-open]'),
    };
}

function closeDocumentViewer() {
    const viewer = getViewerElements();

    if (!viewer) {
        return;
    }

    viewer.modal.hidden = true;
    viewer.modal.setAttribute('aria-hidden', 'true');
    viewer.frame.src = 'about:blank';
    viewer.frame.hidden = true;
    viewer.fallback.hidden = true;
    viewer.openTab.hidden = true;
    document.body.classList.remove('doc-viewer-open');
}

function openDocumentViewer(url, title) {
    const viewer = getViewerElements();

    if (!viewer) {
        window.open(url, '_blank', 'noopener,noreferrer');

        return;
    }

    viewer.title.textContent = title;
    viewer.openTab.href = url;
    viewer.openTab.hidden = false;
    viewer.fallbackLink.href = url;

    if (isPdfUrl(url)) {
        viewer.frame.src = url;
        viewer.frame.hidden = false;
        viewer.fallback.hidden = true;
    } else {
        viewer.frame.hidden = true;
        viewer.fallback.hidden = false;
    }

    viewer.modal.hidden = false;
    viewer.modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('doc-viewer-open');
}

function initDocumentUploadFields(root = document) {
    root.querySelectorAll('.doc-attachment-input').forEach((input) => {
        updateUploadStatus(input);
    });
}

document.addEventListener('click', (event) => {
    const viewTrigger = event.target.closest('[data-doc-view]');

    if (viewTrigger) {
        event.preventDefault();
        openDocumentViewer(viewTrigger.dataset.docUrl, viewTrigger.dataset.docTitle || '');

        return;
    }

    if (event.target.closest('[data-doc-viewer-close]')) {
        closeDocumentViewer();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeDocumentViewer();
    }
});

document.addEventListener('change', (event) => {
    const input = event.target;

    if (!input.matches('.doc-attachment-input')) {
        return;
    }

    const file = input.files?.[0];

    if (file && !isPdfFile(file)) {
        setFileFieldError(input, pdfOnlyMessage(input));
        input.value = '';
        updateUploadStatus(input);

        return;
    }

    if (file && file.size > MAX_FILE_BYTES) {
        setFileFieldError(input, fileTooLargeMessage(input));
        input.value = '';
        updateUploadStatus(input);

        return;
    }

    setFileFieldError(input, '');
    updateUploadStatus(input, file);
});

document.addEventListener('DOMContentLoaded', () => initDocumentUploadFields());
document.addEventListener('alpine:initialized', () => initDocumentUploadFields());

export { initDocumentUploadFields, updateUploadStatus };
