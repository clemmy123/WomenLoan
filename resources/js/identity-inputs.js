const NIN_SEGMENTS = [8, 5, 5, 2];

function digitsOnly(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function formatNinDigits(digits) {
    const parts = [];
    let offset = 0;

    for (const length of NIN_SEGMENTS) {
        const chunk = digits.slice(offset, offset + length);
        if (!chunk) {
            break;
        }
        parts.push(chunk);
        offset += length;
    }

    return parts.join('-');
}

function bindNinInput(input) {
    if (input.dataset.ninBound === '1') {
        return;
    }

    input.dataset.ninBound = '1';

    const sync = () => {
        const digits = digitsOnly(input.value).slice(0, 20);
        input.value = formatNinDigits(digits);
    };

    input.addEventListener('input', sync);
    input.addEventListener('blur', sync);
    input.addEventListener('paste', (event) => {
        event.preventDefault();
        const pasted = event.clipboardData?.getData('text') ?? '';
        input.value = formatNinDigits(digitsOnly(pasted).slice(0, 20));
    });

    sync();
}

function syncPhoneField(field) {
    const localInput = field.querySelector('[data-phone-local]');
    const hiddenInput = field.querySelector('[data-phone-hidden]');

    if (!localInput || !hiddenInput) {
        return;
    }

    const digits = digitsOnly(localInput.value).slice(0, 9);

    if (digits) {
        hiddenInput.value = `255${digits}`;
    }
}

function syncPhoneFields(root = document) {
    root.querySelectorAll('[data-phone-field]').forEach(syncPhoneField);
}

function bindPhoneField(field) {
    if (field.dataset.phoneBound === '1') {
        return;
    }

    field.dataset.phoneBound = '1';

    const localInput = field.querySelector('[data-phone-local]');
    const hiddenInput = field.querySelector('[data-phone-hidden]');

    if (!localInput || !hiddenInput) {
        return;
    }

    const sync = () => {
        const digits = digitsOnly(localInput.value).slice(0, 9);
        localInput.value = digits;

        if (digits) {
            hiddenInput.value = `255${digits}`;
        } else if (!localInput.readOnly) {
            hiddenInput.value = '';
        }
    };

    const syncFromHidden = () => {
        const normalized = digitsOnly(hiddenInput.value);

        if (normalized.startsWith('255') && normalized.length >= 12) {
            localInput.value = normalized.slice(3, 12);
        }
    };

    syncFromHidden();
    sync();

    localInput.addEventListener('input', sync);
    localInput.addEventListener('blur', sync);
    localInput.addEventListener('paste', (event) => {
        event.preventDefault();
        const pasted = digitsOnly(event.clipboardData?.getData('text') ?? '').slice(0, 9);
        localInput.value = pasted;
        sync();
    });

    const form = field.closest('form');

    if (form && form.dataset.phoneSubmitBound !== '1') {
        form.dataset.phoneSubmitBound = '1';
        form.addEventListener('submit', () => syncPhoneFields(form));
    }
}

function initIdentityInputs(root = document) {
    root.querySelectorAll('[data-nin-input]').forEach(bindNinInput);
    root.querySelectorAll('[data-phone-field]').forEach(bindPhoneField);
}

document.addEventListener('DOMContentLoaded', () => initIdentityInputs());
document.addEventListener('alpine:initialized', () => initIdentityInputs());

export { initIdentityInputs, syncPhoneFields, formatNinDigits, digitsOnly };

window.initIdentityInputs = initIdentityInputs;
window.syncPhoneFields = syncPhoneFields;
