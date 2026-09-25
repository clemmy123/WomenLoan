const NIN_SEGMENTS = [8, 5, 5, 2];
const TIN_SEGMENTS = [3, 3, 3];
const NIN_DIGIT_COUNT = 20;
const TIN_DIGIT_COUNT = 9;

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

function formatTinDigits(digits) {
    const parts = [];
    let offset = 0;

    for (const length of TIN_SEGMENTS) {
        const chunk = digits.slice(offset, offset + length);

        if (!chunk) {
            break;
        }

        parts.push(chunk);
        offset += length;
    }

    return parts.join('-');
}

function isNinComplete(value) {
    return digitsOnly(value).length === NIN_DIGIT_COUNT;
}

function isTinComplete(value) {
    return digitsOnly(value).length === TIN_DIGIT_COUNT;
}

function identityIncompleteMessage(input) {
    return input.dataset.incompleteMessage
        ?? (input.matches('[data-tin-input]')
            ? 'Enter the full TIN in format 111-111-111.'
            : 'Enter the full NIN in format 19000000-00000-00000-02.');
}

function showIdentityInputError(input, message = null) {
    const text = message ?? identityIncompleteMessage(input);
    const field = input.closest('[data-identity-field]');
    const errorEl = field?.querySelector('[data-identity-error]');

    input.classList.add('app-identity-invalid');
    input.setCustomValidity(text);

    if (errorEl) {
        errorEl.textContent = text;
        errorEl.hidden = false;
    }
}

function clearIdentityInputError(input) {
    const field = input.closest('[data-identity-field]');
    const errorEl = field?.querySelector('[data-identity-error]');

    input.classList.remove('app-identity-invalid');
    input.setCustomValidity('');

    if (errorEl) {
        errorEl.textContent = '';
        errorEl.hidden = true;
    }
}

function validateIdentityInput(input, { silent = false } = {}) {
    if (input.matches('[data-tin-input]')) {
        if (!isTinComplete(input.value)) {
            if (!silent) {
                showIdentityInputError(input);
            }

            return false;
        }

        clearIdentityInputError(input);

        return true;
    }

    if (input.matches('[data-nin-input]')) {
        if (input.required && !isNinComplete(input.value)) {
            if (!silent) {
                showIdentityInputError(input);
            }

            return false;
        }

        if (isNinComplete(input.value) || digitsOnly(input.value) === '') {
            clearIdentityInputError(input);
        }

        return !input.required || isNinComplete(input.value);
    }

    return true;
}

function bindNinInput(input) {
    if (input.dataset.ninBound === '1') {
        return;
    }

    input.dataset.ninBound = '1';

    const sync = () => {
        const digits = digitsOnly(input.value).slice(0, NIN_DIGIT_COUNT);
        input.value = formatNinDigits(digits);

        if (isNinComplete(input.value)) {
            clearIdentityInputError(input);
        }
    };

    input.addEventListener('input', sync);
    input.addEventListener('blur', () => {
        sync();

        if (digitsOnly(input.value) !== '' && !isNinComplete(input.value)) {
            showIdentityInputError(input);
        }
    });
    input.addEventListener('paste', (event) => {
        event.preventDefault();
        const pasted = event.clipboardData?.getData('text') ?? '';
        input.value = formatNinDigits(digitsOnly(pasted).slice(0, NIN_DIGIT_COUNT));
        sync();
    });

    sync();
}

function bindTinInput(input) {
    if (input.dataset.tinBound === '1') {
        return;
    }

    input.dataset.tinBound = '1';

    const sync = () => {
        const digits = digitsOnly(input.value).slice(0, TIN_DIGIT_COUNT);
        input.value = formatTinDigits(digits);

        if (isTinComplete(input.value)) {
            clearIdentityInputError(input);
        }
    };

    input.addEventListener('input', sync);
    input.addEventListener('blur', () => {
        sync();

        if (digitsOnly(input.value) !== '' && !isTinComplete(input.value)) {
            showIdentityInputError(input);
        }
    });
    input.addEventListener('paste', (event) => {
        event.preventDefault();
        const pasted = event.clipboardData?.getData('text') ?? '';
        input.value = formatTinDigits(digitsOnly(pasted).slice(0, TIN_DIGIT_COUNT));
        sync();
    });

    sync();
}

function isTanzaniaPhone(value) {
    let digits = digitsOnly(value);

    if (digits.startsWith('0') && digits.length === 10) {
        digits = `255${digits.slice(1)}`;
    }

    if (digits.length === 9 && /^[67]/.test(digits)) {
        digits = `255${digits}`;
    }

    return /^255[67]\d{8}$/.test(digits);
}

function phoneInvalidMessage(input) {
    return input.dataset.phoneInvalidMessage
        ?? 'Please enter a valid Tanzania phone number (start with 6 or 7, 9 digits).';
}

function showPhoneFieldError(localInput, message = null) {
    const text = message ?? phoneInvalidMessage(localInput);
    const wrap = localInput.closest('.wizard-field') ?? localInput.closest('[data-phone-field]');
    const errorEl = wrap?.querySelector('[data-wizard-field-error]')
        ?? wrap?.querySelector('[data-phone-error]');

    localInput.classList.add('app-identity-invalid');
    localInput.setCustomValidity(text);

    if (errorEl) {
        errorEl.textContent = text;
        errorEl.hidden = false;
    }
}

function clearPhoneFieldError(localInput) {
    const wrap = localInput.closest('.wizard-field') ?? localInput.closest('[data-phone-field]');
    const errorEl = wrap?.querySelector('[data-wizard-field-error]')
        ?? wrap?.querySelector('[data-phone-error]');

    localInput.classList.remove('app-identity-invalid');
    localInput.setCustomValidity('');

    if (errorEl && errorEl.dataset.serverError !== 'true') {
        errorEl.textContent = '';
        errorEl.hidden = true;
    }
}

function validatePhoneField(field, options = {}) {
    const { silent = false } = options;
    const localInput = field.querySelector('[data-phone-local]');
    const hiddenInput = field.querySelector('[data-phone-hidden]');

    if (!localInput) {
        return true;
    }

    const value = hiddenInput?.value || localInput.value;
    const empty = digitsOnly(value).length === 0;

    if (empty) {
        clearPhoneFieldError(localInput);

        return !localInput.required;
    }

    if (isTanzaniaPhone(value)) {
        clearPhoneFieldError(localInput);

        return true;
    }

    if (!silent) {
        showPhoneFieldError(localInput);
        localInput.reportValidity();
    }

    return false;
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

    localInput.addEventListener('input', () => {
        sync();
        if (isTanzaniaPhone(hiddenInput.value) || digitsOnly(localInput.value).length === 0) {
            clearPhoneFieldError(localInput);
        }
    });
    localInput.addEventListener('blur', () => {
        sync();
        validatePhoneField(field);
    });
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
    root.querySelectorAll('[data-tin-input]').forEach(bindTinInput);
    root.querySelectorAll('[data-phone-field]').forEach(bindPhoneField);
}

document.addEventListener('DOMContentLoaded', () => initIdentityInputs());

export {
    initIdentityInputs,
    syncPhoneFields,
    formatNinDigits,
    formatTinDigits,
    digitsOnly,
    isNinComplete,
    isTinComplete,
    isTanzaniaPhone,
    validatePhoneField,
    showIdentityInputError,
    clearIdentityInputError,
    validateIdentityInput,
};

window.initIdentityInputs = initIdentityInputs;
window.syncPhoneFields = syncPhoneFields;
window.isNinComplete = isNinComplete;
window.isTinComplete = isTinComplete;
window.isTanzaniaPhone = isTanzaniaPhone;
window.validatePhoneField = validatePhoneField;
window.showIdentityInputError = showIdentityInputError;
window.clearIdentityInputError = clearIdentityInputError;
window.validateIdentityInput = validateIdentityInput;
