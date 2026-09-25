function placeKebabMenu(trigger, menu) {
    const gap = 6;
    const pad = 8;
    const rect = trigger.getBoundingClientRect();
    const menuHeight = menu.offsetHeight;
    const menuWidth = menu.offsetWidth;

    let top = rect.bottom + gap;
    if (top + menuHeight > window.innerHeight - pad) {
        top = Math.max(pad, rect.top - menuHeight - gap);
    }

    let left = rect.right - menuWidth;
    if (left < pad) {
        left = pad;
    }
    if (left + menuWidth > window.innerWidth - pad) {
        left = Math.max(pad, window.innerWidth - menuWidth - pad);
    }

    menu.style.cssText = `position:fixed;top:${Math.round(top)}px;left:${Math.round(left)}px;z-index:80;display:block;`;
}

function closeAllKebabs() {
    document.querySelectorAll('[data-kebab-menu].is-open').forEach((menu) => {
        menu.classList.remove('is-open');
        menu.hidden = true;
        menu.style.cssText = '';
        const kebab = menu._kebabRoot;
        kebab?.querySelector('[data-kebab-trigger]')?.setAttribute('aria-expanded', 'false');
    });
}

function initKebabs() {
    document.querySelectorAll('[data-kebab]').forEach((root) => {
        const trigger = root.querySelector('[data-kebab-trigger]');
        const menu = root.querySelector('[data-kebab-menu]');

        if (! trigger || ! menu || trigger.dataset.bound === 'true') {
            return;
        }

        trigger.dataset.bound = 'true';
        menu._kebabRoot = root;

        if (menu.parentElement !== document.body) {
            document.body.appendChild(menu);
        }

        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = menu.hidden;
            closeAllKebabs();

            if (! willOpen) {
                return;
            }

            menu.hidden = false;
            menu.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            placeKebabMenu(trigger, menu);
        });
    });

    document.addEventListener('mousedown', (event) => {
        if (event.target.closest('[data-kebab], [data-kebab-menu]')) {
            return;
        }
        closeAllKebabs();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllKebabs();
        }
    });

    window.addEventListener('scroll', closeAllKebabs, true);
    window.addEventListener('resize', closeAllKebabs);
}

document.addEventListener('DOMContentLoaded', initKebabs);
