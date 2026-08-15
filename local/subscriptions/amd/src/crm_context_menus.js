/* eslint-env amd */
/**
 * CRM contextual row menus.
 *
 * Menus are positioned fixed when opened so they are not clipped by table
 * overflow containers and do not create a second scrollbar near the last rows.
 *
 * @module local_subscriptions/crm_context_menus
 */

const SELECTOR = [
    '.crm-sales-row-actions-menu',
    '.commerce-mail-row-actions-menu',
].join(', ');

const MENU_SELECTOR = [
    '.crm-sales-row-menu',
    '.commerce-mail-row-menu',
].join(', ');

const GAP = 6;
const VIEWPORT_MARGIN = 10;

const resetMenuPosition = (details) => {
    const menu = details.querySelector(MENU_SELECTOR);
    if (!menu) {
        return;
    }

    menu.classList.remove('is-floating');
    menu.style.removeProperty('top');
    menu.style.removeProperty('left');
    menu.style.removeProperty('right');
    menu.style.removeProperty('bottom');
    menu.style.removeProperty('max-height');
};

const closeMenus = (exception = null) => {
    document.querySelectorAll(SELECTOR).forEach((details) => {
        if (details !== exception) {
            details.removeAttribute('open');
            resetMenuPosition(details);
        }
    });
};

const positionMenu = (details) => {
    const summary = details.querySelector(':scope > summary');
    const menu = details.querySelector(MENU_SELECTOR);
    if (!summary || !menu) {
        return;
    }

    menu.classList.add('is-floating');
    menu.style.left = '0px';
    menu.style.top = '0px';
    menu.style.right = 'auto';

    const triggerRect = summary.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    const width = menuRect.width;
    const height = menuRect.height;

    let left = triggerRect.right - width;
    left = Math.max(
        VIEWPORT_MARGIN,
        Math.min(left, window.innerWidth - width - VIEWPORT_MARGIN)
    );

    const spaceBelow = window.innerHeight - triggerRect.bottom - VIEWPORT_MARGIN;
    const spaceAbove = triggerRect.top - VIEWPORT_MARGIN;
    let top;

    if (spaceBelow >= height || spaceBelow >= spaceAbove) {
        top = triggerRect.bottom + GAP;
        menu.style.maxHeight = `${Math.max(120, spaceBelow - GAP)}px`;
    } else {
        top = Math.max(VIEWPORT_MARGIN, triggerRect.top - height - GAP);
        menu.style.maxHeight = `${Math.max(120, spaceAbove - GAP)}px`;
    }

    menu.style.left = `${Math.round(left)}px`;
    menu.style.top = `${Math.round(top)}px`;
};

export const init = () => {
    document.querySelectorAll(SELECTOR).forEach((details) => {
        if (details.dataset.crmContextMenuInitialised === '1') {
            return;
        }

        details.dataset.crmContextMenuInitialised = '1';
        details.addEventListener('toggle', () => {
            if (details.open) {
                closeMenus(details);
                window.requestAnimationFrame(() => positionMenu(details));
            } else {
                resetMenuPosition(details);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest(SELECTOR)) {
            closeMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenus();
        }
    });

    window.addEventListener('resize', () => closeMenus());
    window.addEventListener('scroll', () => closeMenus(), true);
};
