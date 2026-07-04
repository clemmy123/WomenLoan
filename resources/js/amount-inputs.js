function amountDigitsOnly(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function formatAmountDigits(digits) {
    if (!digits) {
        return '';
    }

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function syncAmountField(field) {
    const displayInput = field.querySelector('[data-amount-display]');
    const hiddenInput = field.querySelector('[data-amount-hidden]');

    if (!displayInput || !hiddenInput) {
        return;
    }

    const digits = amountDigitsOnly(displayInput.value).slice(0, 15);
    displayInput.value = formatAmountDigits(digits);
    hiddenInput.value = digits;
}

function syncAmountFields(root = document) {
    root.querySelectorAll('[data-amount-field]').forEach(syncAmountField);
}

function bindAmountField(field) {
    if (field.dataset.amountBound === '1') {
        return;
    }

    field.dataset.amountBound = '1';

    const displayInput = field.querySelector('[data-amount-display]');
    const hiddenInput = field.querySelector('[data-amount-hidden]');

    if (!displayInput || !hiddenInput) {
        return;
    }

    const sync = () => syncAmountField(field);

    if (hiddenInput.value) {
        displayInput.value = formatAmountDigits(amountDigitsOnly(hiddenInput.value));
    }

    displayInput.addEventListener('input', sync);
    displayInput.addEventListener('blur', sync);
    displayInput.addEventListener('paste', (event) => {
        event.preventDefault();
        displayInput.value = amountDigitsOnly(event.clipboardData?.getData('text') ?? '').slice(0, 15);
        sync();
    });

    const form = field.closest('form');

    if (form && form.dataset.amountSubmitBound !== '1') {
        form.dataset.amountSubmitBound = '1';
        form.addEventListener('submit', () => syncAmountFields(form));
    }

    sync();
}

function initAmountInputs(root = document) {
    root.querySelectorAll('[data-amount-field]').forEach(bindAmountField);
}

document.addEventListener('DOMContentLoaded', () => initAmountInputs());
document.addEventListener('alpine:initialized', () => initAmountInputs());

export { initAmountInputs, syncAmountFields, formatAmountDigits, amountDigitsOnly };

window.initAmountInputs = initAmountInputs;
window.syncAmountFields = syncAmountFields;
