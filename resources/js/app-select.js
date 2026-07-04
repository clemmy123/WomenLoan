import { preserveScroll } from './preserve-scroll';

const WRAP_CLASS = 'app-select-wrap';
const OPEN_WRAPS = new Set();

function scrollOptionIntoPanel(panelInner, selected) {
    if (!selected || !panelInner) {
        return;
    }

    const optionTop = selected.offsetTop;
    const optionBottom = optionTop + selected.offsetHeight;
    const viewTop = panelInner.scrollTop;
    const viewBottom = viewTop + panelInner.clientHeight;

    if (optionTop < viewTop) {
        panelInner.scrollTop = optionTop;
    } else if (optionBottom > viewBottom) {
        panelInner.scrollTop = optionBottom - panelInner.clientHeight;
    }
}

function closeAllAppSelects(exceptWrap = null) {
    OPEN_WRAPS.forEach((wrap) => {
        if (wrap !== exceptWrap && wrap._appSelectState) {
            wrap._appSelectState.close();
        }
    });
}

function syncTriggerFromSelect(select, trigger, valueEl, panelInner) {
    const option = select.options[select.selectedIndex];
    const label = option ? option.textContent.trim() : '';
    valueEl.textContent = label;
    valueEl.classList.toggle('is-placeholder', !select.value);

    trigger.disabled = select.disabled;
    trigger.classList.toggle('is-disabled', select.disabled);

    panelInner?.querySelectorAll('.app-select-option').forEach((btn) => {
        btn.classList.toggle('is-selected', btn.dataset.value === select.value);
    });
}

function rebuildPanelOptions(select, panelInner, onSelect) {
    panelInner.innerHTML = '';

    Array.from(select.options).forEach((option) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'app-select-option';
        btn.dataset.value = option.value;
        btn.textContent = option.textContent.trim();
        btn.disabled = option.disabled;

        if (option.value === select.value) {
            btn.classList.add('is-selected');
        }

        if (!option.value) {
            btn.classList.add('is-placeholder-option');
        }

        btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (option.disabled) {
                return;
            }

            preserveScroll(() => {
                select.value = option.value;
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
                onSelect();
            });
        });

        panelInner.appendChild(btn);
    });
}

export function refreshAppSelect(select) {
    const wrap = select.closest(`.${WRAP_CLASS}`);

    if (!wrap?._appSelectState) {
        return;
    }

    wrap._appSelectState.refresh();
}

export function enhanceAppSelect(select) {
    if (select.multiple || select.dataset.appSelectEnhanced === 'true') {
        refreshAppSelect(select);

        return;
    }

    select.dataset.appSelectEnhanced = 'true';

    const wrap = document.createElement('div');
    wrap.className = WRAP_CLASS;
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);

    select.classList.add('app-select-native');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'app-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const valueEl = document.createElement('span');
    valueEl.className = 'app-select-value';

    const chevron = document.createElement('span');
    chevron.className = 'app-select-chevron';
    chevron.setAttribute('aria-hidden', 'true');
    chevron.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';

    trigger.append(valueEl, chevron);

    const panel = document.createElement('div');
    panel.className = 'app-select-panel';
    panel.hidden = true;
    panel.setAttribute('role', 'listbox');

    const panelHeader = document.createElement('div');
    panelHeader.className = 'app-select-panel-header';

    const panelInner = document.createElement('div');
    panelInner.className = 'app-select-panel-inner';

    panel.append(panelHeader, panelInner);
    wrap.append(trigger, panel);

    const portalHost = document.createElement('div');
    portalHost.className = 'app-select-portal';
    portalHost.hidden = true;
    document.body.appendChild(portalHost);

    const mountPanelToPortal = () => {
        portalHost.hidden = false;
        portalHost.appendChild(panel);
    };

    const restorePanelToWrap = () => {
        portalHost.hidden = true;
        wrap.appendChild(panel);
    };

    const placeholderOption = () => Array.from(select.options).find((opt) => !opt.value);
    const updateHeader = () => {
        const placeholder = placeholderOption();
        panelHeader.textContent = placeholder?.textContent.trim() || select.labels?.[0]?.textContent?.trim() || '';
    };

    const close = () => {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        wrap.classList.remove('is-open');
        OPEN_WRAPS.delete(wrap);
        panel.style.position = '';
        panel.style.top = '';
        panel.style.left = '';
        panel.style.width = '';
        panel.style.right = '';
        panel.style.zIndex = '';
        restorePanelToWrap();
    };

    const positionPanel = () => {
        const rect = trigger.getBoundingClientRect();
        panel.style.position = 'fixed';
        panel.style.left = `${rect.left}px`;
        panel.style.width = `${rect.width}px`;
        panel.style.right = 'auto';
        panel.style.zIndex = '9999';

        const panelHeight = panel.offsetHeight || 220;
        const spaceBelow = window.innerHeight - rect.bottom;
        const openUpward = spaceBelow < panelHeight + 12 && rect.top > panelHeight + 12;

        if (openUpward) {
            panel.style.top = `${Math.max(8, rect.top - panelHeight - 6)}px`;
            wrap.classList.add('opens-up');
        } else {
            panel.style.top = `${rect.bottom + 6}px`;
            wrap.classList.remove('opens-up');
        }
    };

    const open = () => {
        if (select.disabled) {
            return;
        }

        closeAllAppSelects(wrap);
        updateHeader();
        mountPanelToPortal();
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        wrap.classList.add('is-open');
        OPEN_WRAPS.add(wrap);

        requestAnimationFrame(() => {
            positionPanel();
            requestAnimationFrame(() => {
                positionPanel();
                const selected = panelInner.querySelector('.app-select-option.is-selected');
                scrollOptionIntoPanel(panelInner, selected);
            });
        });
    };

    const refresh = () => {
        preserveScroll(() => {
            rebuildPanelOptions(select, panelInner, () => {
                syncTriggerFromSelect(select, trigger, valueEl, panelInner);
                close();
            });
            updateHeader();
            syncTriggerFromSelect(select, trigger, valueEl, panelInner);
        });
    };

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (wrap.classList.contains('is-open')) {
            close();
        } else {
            open();
        }
    });

    select.addEventListener('change', () => syncTriggerFromSelect(select, trigger, valueEl, panelInner));

    const observer = new MutationObserver(() => refresh());
    observer.observe(select, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['disabled', 'value'],
    });

    wrap._appSelectState = { refresh, close, open, positionPanel };

    refresh();
}

export function enhanceAllAppSelects(root = document) {
    root.querySelectorAll('select.app-select').forEach((select) => {
        enhanceAppSelect(select);
    });
}

function scheduleEnhance() {
    requestAnimationFrame(() => enhanceAllAppSelects());
}

document.addEventListener('DOMContentLoaded', scheduleEnhance);
document.addEventListener('alpine:initialized', scheduleEnhance);

document.addEventListener('click', (event) => {
    if (event.target.closest(`.${WRAP_CLASS}`) || event.target.closest('.app-select-panel')) {
        return;
    }

    closeAllAppSelects();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeAllAppSelects();
    }
});

window.addEventListener('resize', () => {
    OPEN_WRAPS.forEach((wrap) => {
        if (wrap.classList.contains('is-open') && wrap._appSelectState) {
            wrap._appSelectState.positionPanel?.();
        }
    });
});

window.addEventListener(
    'scroll',
    () => {
        OPEN_WRAPS.forEach((wrap) => {
            if (wrap.classList.contains('is-open') && wrap._appSelectState) {
                wrap._appSelectState.positionPanel?.();
            }
        });
    },
    true,
);

window.AppSelect = {
    enhanceAllAppSelects,
    enhanceAppSelect,
    refreshAppSelect,
};
