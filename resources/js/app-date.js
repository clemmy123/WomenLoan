const OPEN_PICKERS = new Set();
const MONTHS = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

function pad(n) {
    return String(n).padStart(2, '0');
}

function parseDateValue(value) {
    if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
    }
    const [y, m, d] = value.split('-').map(Number);
    const date = new Date(y, m - 1, d);
    if (date.getFullYear() !== y || date.getMonth() !== m - 1 || date.getDate() !== d) {
        return null;
    }
    return date;
}

function formatIso(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function formatDisplay(date) {
    return date.toLocaleDateString(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function clampDate(date, min, max) {
    let next = date;
    if (min && next < min) {
        next = new Date(min.getTime());
    }
    if (max && next > max) {
        next = new Date(max.getTime());
    }
    return next;
}

function daysInMonth(year, monthIndex) {
    return new Date(year, monthIndex + 1, 0).getDate();
}

function closeAll(except = null) {
    OPEN_PICKERS.forEach((state) => {
        if (state !== except) {
            state.close();
        }
    });
}

function enhanceDateInput(input) {
    if (input.dataset.appDateReady === '1' || input.closest('.app-date-wrap')) {
        return;
    }

    input.dataset.appDateReady = '1';

    const wrap = document.createElement('div');
    wrap.className = 'app-date-wrap';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);

    input.classList.add('app-date-native');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'app-date-trigger';
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-expanded', 'false');

    const valueEl = document.createElement('span');
    valueEl.className = 'app-date-value';
    trigger.appendChild(valueEl);

    const icon = document.createElement('span');
    icon.className = 'app-date-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.innerHTML = `<svg viewBox="0 0 24 24" fill="none"><path d="M8 3v3M16 3v3M4 9h16M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>`;
    trigger.appendChild(icon);

    wrap.appendChild(trigger);

    const panel = document.createElement('div');
    panel.className = 'app-date-panel';
    panel.hidden = true;
    panel.innerHTML = `
        <div class="app-date-panel-head">
            <select class="app-date-month" aria-label="Month"></select>
            <select class="app-date-year" aria-label="Year"></select>
        </div>
        <div class="app-date-weekdays">
            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
        </div>
        <div class="app-date-grid"></div>
        <div class="app-date-actions">
            <button type="button" class="app-date-today">Today</button>
            <button type="button" class="app-date-clear">Clear</button>
        </div>
    `;
    wrap.appendChild(panel);

    const monthSelect = panel.querySelector('.app-date-month');
    const yearSelect = panel.querySelector('.app-date-year');
    const grid = panel.querySelector('.app-date-grid');
    const todayBtn = panel.querySelector('.app-date-today');
    const clearBtn = panel.querySelector('.app-date-clear');

    MONTHS.forEach((label, index) => {
        const option = document.createElement('option');
        option.value = String(index);
        option.textContent = label;
        monthSelect.appendChild(option);
    });

    let viewYear;
    let viewMonth;
    let open = false;

    const state = {
        close() {
            if (!open) {
                return;
            }
            open = false;
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            wrap.classList.remove('is-open');
            OPEN_PICKERS.delete(state);
        },
        open() {
            if (input.disabled || input.readOnly) {
                return;
            }
            closeAll(state);
            const selected = parseDateValue(input.value) || new Date();
            const min = parseDateValue(input.min);
            const max = parseDateValue(input.max);
            const base = clampDate(selected, min, max);
            viewYear = base.getFullYear();
            viewMonth = base.getMonth();
            rebuildYearOptions(min, max);
            monthSelect.value = String(viewMonth);
            yearSelect.value = String(viewYear);
            renderGrid();
            open = true;
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            wrap.classList.add('is-open');
            OPEN_PICKERS.add(state);
        },
        refresh() {
            syncTrigger();
            if (open) {
                renderGrid();
            }
        },
    };

    function rebuildYearOptions(min, max) {
        const nowYear = new Date().getFullYear();
        const minYear = min ? min.getFullYear() : nowYear - 100;
        const maxYear = max ? max.getFullYear() : nowYear + 20;
        yearSelect.innerHTML = '';
        for (let y = maxYear; y >= minYear; y--) {
            const option = document.createElement('option');
            option.value = String(y);
            option.textContent = String(y);
            yearSelect.appendChild(option);
        }
    }

    function syncTrigger() {
        const date = parseDateValue(input.value);
        if (date) {
            valueEl.textContent = formatDisplay(date);
            valueEl.classList.remove('is-placeholder');
        } else {
            valueEl.textContent = input.placeholder || 'Select date';
            valueEl.classList.add('is-placeholder');
        }
        trigger.disabled = input.disabled;
        trigger.classList.toggle('is-disabled', input.disabled || input.readOnly);
        wrap.classList.toggle('is-readonly', input.readOnly);
    }

    function setValue(iso) {
        if (input.value === iso) {
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            syncTrigger();
            return;
        }
        input.value = iso;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        syncTrigger();
    }

    function renderGrid() {
        const min = parseDateValue(input.min);
        const max = parseDateValue(input.max);
        const selected = parseDateValue(input.value);
        const firstDay = new Date(viewYear, viewMonth, 1).getDay();
        const totalDays = daysInMonth(viewYear, viewMonth);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        grid.innerHTML = '';

        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('span');
            empty.className = 'app-date-day is-empty';
            grid.appendChild(empty);
        }

        for (let day = 1; day <= totalDays; day++) {
            const date = new Date(viewYear, viewMonth, day);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'app-date-day';
            btn.textContent = String(day);

            const disabled = (min && date < min) || (max && date > max);
            btn.disabled = disabled;

            if (selected && formatIso(selected) === formatIso(date)) {
                btn.classList.add('is-selected');
            }
            if (formatIso(today) === formatIso(date)) {
                btn.classList.add('is-today');
            }

            btn.addEventListener('click', () => {
                setValue(formatIso(date));
                state.close();
            });

            grid.appendChild(btn);
        }
    }

    monthSelect.addEventListener('change', () => {
        viewMonth = Number(monthSelect.value);
        renderGrid();
    });

    yearSelect.addEventListener('change', () => {
        viewYear = Number(yearSelect.value);
        renderGrid();
    });

    todayBtn.addEventListener('click', () => {
        const min = parseDateValue(input.min);
        const max = parseDateValue(input.max);
        const today = clampDate(new Date(), min, max);
        setValue(formatIso(today));
        state.close();
    });

    clearBtn.addEventListener('click', () => {
        if (input.required) {
            return;
        }
        setValue('');
        state.close();
    });

    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        if (open) {
            state.close();
        } else {
            state.open();
        }
    });

    // Block native OS/browser date UI without breaking the input value.
    ['click', 'mousedown', 'keydown', 'focus'].forEach((evt) => {
        input.addEventListener(evt, (e) => {
            if (input.disabled || input.readOnly) {
                return;
            }
            if (evt === 'keydown' && !['Enter', ' ', 'ArrowDown'].includes(e.key)) {
                return;
            }
            e.preventDefault();
            if (!open) {
                state.open();
            }
            trigger.focus();
        });
    });

    input.addEventListener('change', syncTrigger);
    input.addEventListener('input', syncTrigger);

    // Keep display in sync when Alpine/x-model updates the value property.
    if (!input._appDateValuePatched) {
        const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        Object.defineProperty(input, 'value', {
            configurable: true,
            get() {
                return descriptor.get.call(this);
            },
            set(next) {
                descriptor.set.call(this, next);
                queueMicrotask(() => wrap._appDateState?.refresh());
            },
        });
        input._appDateValuePatched = true;
    }

    const observer = new MutationObserver(() => {
        syncTrigger();
        if (open) {
            const min = parseDateValue(input.min);
            const max = parseDateValue(input.max);
            rebuildYearOptions(min, max);
            yearSelect.value = String(viewYear);
            renderGrid();
        }
    });
    observer.observe(input, { attributes: true, attributeFilter: ['value', 'min', 'max', 'disabled', 'readonly'] });

    wrap._appDateState = state;
    syncTrigger();
}

export function refreshAppDate(input) {
    if (!input) {
        return;
    }
    if (!input.closest('.app-date-wrap')) {
        enhanceDateInput(input);
        return;
    }
    input.closest('.app-date-wrap')?._appDateState?.refresh();
}

export function initAppDates(root = document) {
    root.querySelectorAll('input[type="date"]').forEach((input) => enhanceDateInput(input));
}

document.addEventListener('click', (e) => {
    document.querySelectorAll('.app-date-wrap.is-open').forEach((wrap) => {
        if (!wrap.contains(e.target)) {
            wrap._appDateState?.close();
        }
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAll();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initAppDates();

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }
                if (node.matches?.('input[type="date"]')) {
                    enhanceDateInput(node);
                }
                node.querySelectorAll?.('input[type="date"]').forEach((input) => enhanceDateInput(input));
            });
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

window.AppDate = {
    initAppDates,
    refreshAppDate,
};
