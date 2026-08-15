/* eslint-env amd */
/**
 * Initialises autonomous CRM shell browser integrations.
 *
 * @module local_subscriptions/crm_shell
 */

const SELECTORS = {
    shell: '[data-crm-shell]',
    navigation: '[data-crm-navigation]',
    navigationPanel: '[data-crm-navigation-panel]',
    navigationToggle: '[data-crm-navigation-toggle]',
    navigationLink: '[data-crm-navigation-link]',
    navigationMenuToggle: '[data-crm-navigation-menu-toggle]',
    navigationMenuItem: '.crm-app-navigation-item.has-submenu',
    commandToggle: '[data-crm-command-open]',
    commandCenter: '.campusfr-command-center',
    commandTrigger: '.campusfr-command-trigger',
    topbarDetails:
        '.crm-app-topbar-admin, ' +
        '.crm-app-topbar-language, ' +
        '.crm-app-topbar-user',
    main: '#local-subscriptions-crm-main'
};

const MOBILE_MEDIA_QUERY =
    '(max-width: 767.98px)';

/**
 * Replaces the favicon omitted by the embedded theme layout.
 *
 * @param {String} faviconUrl
 */
const initialiseFavicon = (faviconUrl) => {
    if (
        typeof faviconUrl !== 'string' ||
        faviconUrl.trim() === ''
    ) {
        return;
    }

    const head = document.head;

    if (!head) {
        return;
    }

    head.querySelectorAll(
        'link[rel="icon"], ' +
        'link[rel="shortcut icon"]'
    ).forEach((element) => {
        element.remove();
    });

    const favicon =
        document.createElement('link');

    favicon.rel = 'icon';
    favicon.href = faviconUrl;

    head.appendChild(favicon);

    const shortcut =
        document.createElement('link');

    shortcut.rel = 'shortcut icon';
    shortcut.href = faviconUrl;

    head.appendChild(shortcut);
};

/**
 * Returns whether the mobile shell layout is active.
 *
 * @return {Boolean}
 */
const isMobile = () => {
    return window.matchMedia(
        MOBILE_MEDIA_QUERY
    ).matches;
};

/**
 * Updates the mobile navigation state.
 *
 * @param {HTMLElement} shell
 * @param {Boolean} open
 * @param {Boolean} restoreFocus
 * @param {Boolean} focusFirstLink
 */
const setNavigationOpen = (
    shell,
    open,
    restoreFocus = false,
    focusFirstLink = false
) => {
    const toggle =
        shell.querySelector(
            SELECTORS.navigationToggle
        );

    const panel =
        shell.querySelector(
            SELECTORS.navigationPanel
        );

    if (!toggle || !panel) {
        return;
    }

    const mobile = isMobile();

    const shouldOpen =
        Boolean(open && mobile);

    shell.classList.toggle(
        'is-navigation-open',
        shouldOpen
    );

    panel.classList.toggle(
        'is-open',
        shouldOpen
    );

    toggle.setAttribute(
        'aria-expanded',
        shouldOpen ? 'true' : 'false'
    );

    toggle.setAttribute(
        'aria-label',
        shouldOpen
            ? toggle.dataset.closeLabel || ''
            : toggle.dataset.openLabel || ''
    );

    if (mobile) {
        panel.setAttribute(
            'aria-hidden',
            shouldOpen ? 'false' : 'true'
        );

        if ('inert' in panel) {
            panel.inert = !shouldOpen;
        }
    } else {
        panel.removeAttribute(
            'aria-hidden'
        );

        if ('inert' in panel) {
            panel.inert = false;
        }
    }

    document.body.classList.toggle(
        'crm-navigation-overlay-open',
        shouldOpen
    );

    if (
        shouldOpen &&
        focusFirstLink
    ) {
        const firstlink =
            panel.querySelector(
                SELECTORS.navigationLink
            );

        if (firstlink) {
            firstlink.focus();
        }
    } else if (restoreFocus) {
        toggle.focus();
    }
};

/**
 * Closes all open topbar details except one optional element.
 *
 * @param {HTMLElement} shell
 * @param {HTMLElement|null} exception
 */
const closeTopbarMenus = (
    shell,
    exception = null
) => {
    shell.querySelectorAll(
        SELECTORS.topbarDetails
    ).forEach((details) => {
        if (details !== exception) {
            details.removeAttribute('open');
        }
    });
};


/**
 * Closes CRM contextual navigation menus except one optional item.
 *
 * @param {HTMLElement} shell
 * @param {HTMLElement|null} exception
 */
const closeNavigationMenus = (
    shell,
    exception = null
) => {
    shell.querySelectorAll(
        SELECTORS.navigationMenuItem
    ).forEach((item) => {
        if (item === exception) {
            return;
        }

        item.classList.remove('is-menu-open');

        const toggle = item.querySelector(
            SELECTORS.navigationMenuToggle
        );

        if (toggle) {
            toggle.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    });
};

/**
 * Connects the topbar shortcut to the actual Command Center.
 *
 * @param {HTMLElement} shell
 */
const initialiseCommandCenterButton = (
    shell
) => {
    const button =
        shell.querySelector(
            SELECTORS.commandToggle
        );

    if (!button) {
        return;
    }

    const commandCenter =
        document.querySelector(
            SELECTORS.commandCenter
        );

    const commandTrigger =
        commandCenter
            ? commandCenter.querySelector(
                SELECTORS.commandTrigger
            )
            : null;

    if (!commandCenter || !commandTrigger) {
        button.classList.add('d-none');
        return;
    }

    button.addEventListener(
        'click',
        () => {
            closeTopbarMenus(shell);
            setNavigationOpen(
                shell,
                false
            );

            commandTrigger.click();
        }
    );
};

/**
 * Initialises one CRM shell.
 *
 * @param {HTMLElement} shell
 */
const initialiseShell = (shell) => {
    if (
        shell.dataset.crmShellInitialised ===
        '1'
    ) {
        return;
    }

    shell.dataset.crmShellInitialised =
        '1';

    const navigationToggle =
        shell.querySelector(
            SELECTORS.navigationToggle
        );

    setNavigationOpen(
        shell,
        false
    );

    if (navigationToggle) {
        navigationToggle.addEventListener(
            'click',
            () => {
                const open =
                    navigationToggle.getAttribute(
                        'aria-expanded'
                    ) !== 'true';

                closeTopbarMenus(shell);

                setNavigationOpen(
                    shell,
                    open,
                    false,
                    open
                );
            }
        );
    }

    shell.querySelectorAll(
        SELECTORS.navigationLink
    ).forEach((link) => {
        link.addEventListener(
            'click',
            () => {
                setNavigationOpen(
                    shell,
                    false
                );
            }
        );
    });

    shell.querySelectorAll(
        SELECTORS.navigationMenuToggle
    ).forEach((toggle) => {
        toggle.addEventListener(
            'click',
            (event) => {
                event.preventDefault();
                event.stopPropagation();

                const item = toggle.closest(
                    SELECTORS.navigationMenuItem
                );

                if (!item) {
                    return;
                }

                const open =
                    !item.classList.contains(
                        'is-menu-open'
                    );

                closeNavigationMenus(
                    shell,
                    open ? item : null
                );
                closeTopbarMenus(shell);

                item.classList.toggle(
                    'is-menu-open',
                    open
                );
                toggle.setAttribute(
                    'aria-expanded',
                    open ? 'true' : 'false'
                );
            }
        );
    });

    shell.querySelectorAll(
        SELECTORS.topbarDetails
    ).forEach((details) => {
        details.addEventListener(
            'toggle',
            () => {
                if (!details.open) {
                    return;
                }

                setNavigationOpen(
                    shell,
                    false
                );

                closeTopbarMenus(
                    shell,
                    details
                );
            }
        );
    });

    shell.addEventListener(
        'click',
        (event) => {
            const insideTopbarMenu =
                event.target.closest(
                    SELECTORS.topbarDetails
                );

            if (!insideTopbarMenu) {
                closeTopbarMenus(shell);
            }

            const insideNavigationMenu =
                event.target.closest(
                    SELECTORS.navigationMenuItem
                );

            if (!insideNavigationMenu) {
                closeNavigationMenus(shell);
            }

            if (
                !isMobile() ||
                !shell.classList.contains(
                    'is-navigation-open'
                )
            ) {
                return;
            }

            const insideNavigation =
                event.target.closest(
                    SELECTORS.navigation
                );

            const insideToggle =
                event.target.closest(
                    SELECTORS.navigationToggle
                );

            if (
                !insideNavigation &&
                !insideToggle
            ) {
                setNavigationOpen(
                    shell,
                    false,
                    true
                );
            }
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const navigationWasOpen =
                shell.classList.contains(
                    'is-navigation-open'
                );

            closeTopbarMenus(shell);
            closeNavigationMenus(shell);

            setNavigationOpen(
                shell,
                false,
                navigationWasOpen
            );
        }
    );

    const mediaQuery =
        window.matchMedia(
            MOBILE_MEDIA_QUERY
        );

    const handleResponsiveChange = () => {
        closeNavigationMenus(shell);

        if (!mediaQuery.matches) {
            setNavigationOpen(
                shell,
                false
            );
        }
    };

    if (
        typeof mediaQuery.addEventListener ===
        'function'
    ) {
        mediaQuery.addEventListener(
            'change',
            handleResponsiveChange
        );
    } else if (
        typeof mediaQuery.addListener ===
        'function'
    ) {
        mediaQuery.addListener(
            handleResponsiveChange
        );
    }

    initialiseCommandCenterButton(shell);
};

/**
 * Public AMD entry point.
 *
 * @param {String} faviconUrl
 */
export const init = (faviconUrl = '') => {
    initialiseFavicon(faviconUrl);

    document.querySelectorAll(
        SELECTORS.shell
    ).forEach(initialiseShell);
};