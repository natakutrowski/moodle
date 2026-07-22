/* eslint-env amd */
/**
 * Generic CRM Workspace personalization UI.
 *
 * @module local_subscriptions/workspace_personalization
 */
import Ajax from 'core/ajax';
import Notification from 'core/notification';

const SELECTORS = {
    root:
        '[data-region="workspace-edit-controller"]',
    workspace:
        '[data-region="crm-workspace"]',
    workspaceZone:
        '[data-region="workspace-zone"]',
    workspaceItem:
        '[data-region="workspace-item"]',
    panel:
        '[data-region="workspace-personalization-panel"], ' +
        '[data-region="dashboard-personalization-panel"]',
    open:
        '[data-action="open-workspace-personalization"], ' +
        '[data-action="open-dashboard-personalization"]',

    close:
        '[data-action="close-workspace-personalization"], ' +
        '[data-action="close-dashboard-personalization"]',

    save:
        '[data-action="save-workspace-personalization"], ' +
        '[data-action="save-dashboard-personalization"]',

    reset:
        '[data-action="reset-workspace-personalization"], ' +
        '[data-action="reset-dashboard-personalization"]',
    toolbarSave:
        '[data-action="workspace-toolbar-save"]',
    toolbarReset:
        '[data-action="workspace-toolbar-reset"]',
    toolbarCancel:
        '[data-action="workspace-toolbar-cancel"]',
    list:
        '.crm-workspace-personalization-list, ' +
        '.crm-dashboard-personalization-list',

    card:
        '.crm-workspace-personalization-item, ' +
        '.crm-dashboard-personalization-card',

    visibility:
        '.crm-workspace-item-visibility, ' +
        '.crm-dashboard-card-visibility',
    directCard:
        ':scope > .crm-workspace-personalization-item, ' +
        ':scope > .crm-dashboard-personalization-card',
};

const WORKSPACE_EVENTS = {
    requestEnter:
        'local-subscriptions:workspace-edit-request-enter',
    requestExit:
        'local-subscriptions:workspace-edit-request-exit',
    changed:
        'local-subscriptions:workspace-edit-changed',
    saving:
        'local-subscriptions:workspace-edit-saving',
    saveFailed:
        'local-subscriptions:workspace-edit-save-failed',
    orderChanged:
        'local-subscriptions:workspace-order-changed',
};

let toolbarEventsRegistered = false;
let workspaceOrderEventsRegistered = false;

/**
 * Dispatches a generic Workspace event.
 *
 * @param {HTMLElement} root
 * @param {string} name
 * @param {Object} detail
 */
const dispatchWorkspaceEvent = (
    root,
    name,
    detail = {}
) => {
    root.dispatchEvent(
        new CustomEvent(name, {
            bubbles: true,
            detail: {
                workspace:
                    root.dataset.workspace || '',
                ...detail,
            },
        })
    );
};

/**
 * Finds the personalization controller for a Workspace.
 *
 * @param {string} workspaceKey
 * @returns {HTMLElement|null}
 */
const findController = (workspaceKey) => {
    if (!workspaceKey) {
        return null;
    }

    return document.querySelector(
        `${SELECTORS.root}` +
        `[data-workspace="${CSS.escape(workspaceKey)}"]`
    );
};

/**
 * Opens the personalization panel.
 *
 * @param {HTMLElement} root
 */
const openPanel = (root) => {
    const panel = root.querySelector(
        SELECTORS.panel
    );

    const button = root.querySelector(
        SELECTORS.open
    );

    if (!panel || !button) {
        return;
    }

    dispatchWorkspaceEvent(
        root,
        WORKSPACE_EVENTS.requestEnter
    );

    panel.hidden = false;

    button.setAttribute(
        'aria-expanded',
        'true'
    );

    const close = panel.querySelector(
        SELECTORS.close
    );

    if (close) {
        close.focus();
    } else {
        panel.focus();
    }
};

/**
 * Closes the personalization panel.
 *
 * @param {HTMLElement} root
 * @param {boolean} restore
 * @param {string} reason
 */
const closePanel = (
    root,
    restore = true,
    reason = 'cancel'
) => {
    const panel = root.querySelector(
        SELECTORS.panel
    );

    const button = root.querySelector(
        SELECTORS.open
    );

    if (!panel || !button) {
        return;
    }

    panel.hidden = true;

    button.setAttribute(
        'aria-expanded',
        'false'
    );

    dispatchWorkspaceEvent(
        root,
        WORKSPACE_EVENTS.requestExit,
        {
            restore,
            reason,
        }
    );

    if (restore) {
        button.focus();
    }

};

/**
 * Marks the current layout draft as modified.
 *
 * @param {HTMLElement} root
 */
const markChanged = (root) => {
    dispatchWorkspaceEvent(
        root,
        WORKSPACE_EVENTS.changed
    );
};

/**
 * Synchronizes one personalization list with the rendered Workspace zone.
 *
 * Hidden items are not rendered in the Workspace. Their relative slots
 * in the personalization list are preserved while the visible item slots
 * receive the new grid order.
 *
 * @param {HTMLElement} root
 * @param {string} zoneKey
 * @param {string[]} visibleOrder
 */
const synchronizePanelZone = (
    root,
    zoneKey,
    visibleOrder
) => {
    const lists = [
        ...root.querySelectorAll(
            SELECTORS.list
        ),
    ];

    const list = lists.find((candidate) => {
        return candidate.dataset.zone === zoneKey;
    });

    if (!list) {
        return;
    }

    const cards = [
        ...list.querySelectorAll(
            SELECTORS.directCard
        ),
    ];

    const cardsByKey = new Map();

    cards.forEach((card) => {
        const key = card.dataset.cardKey || '';

        if (key) {
            cardsByKey.set(key, card);
        }
    });

    const visibleKeys = visibleOrder.filter(
        (key) => {
            return cardsByKey.has(key);
        }
    );

    const visibleKeySet = new Set(
        visibleKeys
    );

    let visibleIndex = 0;

    const mergedOrder = cards.map((card) => {
        const key = card.dataset.cardKey || '';

        if (
            visibleKeySet.has(key)
            && visibleIndex < visibleKeys.length
        ) {
            const nextKey =
                visibleKeys[visibleIndex];

            visibleIndex += 1;

            return nextKey;
        }

        return key;
    });

    /*
     * Defensive fallback for a visible item that did not occupy an
     * existing visible slot in the legacy personalization list.
     */
    visibleKeys.forEach((key) => {
        if (!mergedOrder.includes(key)) {
            mergedOrder.push(key);
        }
    });

    const appended = new Set();

    mergedOrder.forEach((key) => {
        const card = cardsByKey.get(key);

        if (
            !card
            || appended.has(key)
        ) {
            return;
        }

        list.appendChild(card);
        appended.add(key);
    });

    cards.forEach((card) => {
        const key = card.dataset.cardKey || '';

        if (!appended.has(key)) {
            list.appendChild(card);
        }
    });
};

/**
 * Synchronizes all rendered Workspace zones with the
 * personalization panel.
 *
 * @param {HTMLElement} root
 * @param {HTMLElement} workspace
 */
const synchronizePanelFromWorkspace = (
    root,
    workspace
) => {
    workspace.querySelectorAll(
        SELECTORS.workspaceZone
    ).forEach((zone) => {
        const zoneKey =
            zone.dataset.workspaceZone || '';

        if (!zoneKey) {
            return;
        }

        const visibleOrder = [
            ...zone.querySelectorAll(
                `:scope > ${SELECTORS.workspaceItem}`
            ),
        ].map((item) => {
            return item.dataset.workspaceItem || '';
        }).filter(Boolean);

        synchronizePanelZone(
            root,
            zoneKey,
            visibleOrder
        );
    });
};

/**
 * Serializes the current Workspace layout.
 *
 * @param {HTMLElement} root
 * @returns {Object}
 */
const serializeLayout = (root) => {
    const hidden = [];
    const order = {};

    root.querySelectorAll(
        SELECTORS.visibility
    ).forEach((checkbox) => {
        if (!checkbox.checked) {
            hidden.push(
                checkbox.dataset.cardKey
            );
        }
    });

    root.querySelectorAll(
        SELECTORS.list
    ).forEach((list) => {
        const zone = list.dataset.zone;

        if (!zone) {
            return;
        }

        order[zone] = [];

        list.querySelectorAll(
            SELECTORS.card
        ).forEach((card) => {
            const key =
                card.dataset.cardKey;

            if (key) {
                order[zone].push(key);
            }
        });
    });

    return {
        version: 2,
        hidden,
        order,
    };
};

/**
 * Persists one Workspace layout.
 *
 * Generic endpoints receive the Workspace key. Compatibility
 * endpoints retain their historical argument contract.
 *
 * @param {HTMLElement} root
 * @param {string} action
 * @param {Object|null} layout
 * @returns {Promise}
 */
const persist = (
    root,
    action,
    layout = null
) => {
    const methodname =
        root.dataset.saveMethod ||
        'local_subscriptions_save_workspace_layout';

    const args = {
        action,
        layout: layout === null
            ? ''
            : JSON.stringify(layout),
    };

    if (
        methodname ===
        'local_subscriptions_save_workspace_layout'
    ) {
        const workspace =
            root.dataset.workspace || '';

        if (workspace === '') {
            throw new Error(
                'Workspace personalization requires a Workspace key.'
            );
        }

        args.workspace = workspace;
    }

    return Ajax.call([{
        methodname,
        args,
    }])[0];
};

/**
 * Sets the legacy panel busy state.
 *
 * The generic Toolbar has its own state management.
 *
 * @param {HTMLElement} root
 * @param {boolean} busy
 */
const setBusy = (
    root,
    busy
) => {
    root.setAttribute(
        'aria-busy',
        busy ? 'true' : 'false'
    );

    root.querySelectorAll('button')
        .forEach((button) => {
            button.disabled = busy;
        });
};

/**
 * Displays the Workspace-specific save error when available.
 *
 * @param {HTMLElement} root
 * @param {Error} error
 */
const showSaveError = (
    root,
    error
) => {
    const message =
        root.dataset.saveError || '';

    if (message) {
        Notification.alert(
            '',
            message
        );

        return;
    }

    Notification.exception(error);
};

/**
 * Saves the current layout.
 *
 * @param {HTMLElement} root
 */
const save = async(root) => {
    dispatchWorkspaceEvent(
        root,
        WORKSPACE_EVENTS.saving
    );

    setBusy(root, true);

    try {
        await persist(
            root,
            'save',
            serializeLayout(root)
        );

        window.location.reload();
    } catch (error) {
        setBusy(root, false);

        dispatchWorkspaceEvent(
            root,
            WORKSPACE_EVENTS.saveFailed
        );

        showSaveError(
            root,
            error
        );
    }
};

/**
 * Restores the persisted default layout.
 *
 * @param {HTMLElement} root
 */
const reset = async(root) => {
    const message =
        root.dataset.resetConfirm || '';

    if (
        message
        && !window.confirm(message)
    ) {
        return;
    }

    dispatchWorkspaceEvent(
        root,
        WORKSPACE_EVENTS.saving
    );

    setBusy(root, true);

    try {
        await persist(
            root,
            'reset'
        );
        window.location.reload();
    } catch (error) {
        setBusy(root, false);

        dispatchWorkspaceEvent(
            root,
            WORKSPACE_EVENTS.saveFailed
        );

        showSaveError(
            root,
            error
        );
    }
};

/**
 * Registers the customization-panel controls.
 *
 * @param {HTMLElement} root
 */
const registerEvents = (root) => {
    root.addEventListener(
        'change',
        (event) => {
            if (
                event.target.matches(
                    SELECTORS.visibility
                )
            ) {
                const card = event.target.closest(
                    SELECTORS.card
                );

                if (card) {
                    card.classList.toggle(
                        'is-dashboard-item-hidden',
                        !event.target.checked
                    );
                }

                markChanged(root);
            }
        }
    );

    root.addEventListener(
        'click',
        (event) => {
            const open =
                event.target.closest(
                    SELECTORS.open
                );

            if (open) {
                openPanel(root);
                return;
            }

            const close =
                event.target.closest(
                    SELECTORS.close
                );

            if (close) {
                closePanel(
                    root,
                    true,
                    'cancel'
                );
                return;
            }

            if (
                event.target.closest(
                    SELECTORS.save
                )
            ) {
                save(root);
                return;
            }

            if (
                event.target.closest(
                    SELECTORS.reset
                )
            ) {
                reset(root);
                return;
            }
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {
            const panel =
                root.querySelector(
                    SELECTORS.panel
                );

            if (
                event.key === 'Escape'
                && panel
                && !panel.hidden
            ) {
                event.preventDefault();
                event.stopPropagation();

                closePanel(
                    root,
                    true,
                    'escape'
                );
            }
        }
    );

    root.addEventListener(
        'keydown',
        (event) => {
            if (event.key !== 'Tab') {
                return;
            }

            const panel = root.querySelector(
                SELECTORS.panel
            );

            if (!panel || panel.hidden) {
                return;
            }

            const focusable = [
                ...panel.querySelectorAll(
                    [
                        'button:not([disabled])',
                        'input:not([disabled])',
                        'select:not([disabled])',
                        'textarea:not([disabled])',
                        'a[href]',
                        '[tabindex]:not([tabindex="-1"])',
                    ].join(',')
                ),
            ].filter((element) => {
                return !element.hidden
                    && element.offsetParent !== null;
            });

            if (focusable.length === 0) {
                event.preventDefault();
                panel.focus();
                return;
            }

            const first = focusable[0];
            const last =
                focusable[focusable.length - 1];

            if (
                event.shiftKey
                && document.activeElement === first
            ) {
                event.preventDefault();
                last.focus();
                return;
            }

            if (
                !event.shiftKey
                && document.activeElement === last
            ) {
                event.preventDefault();
                first.focus();
            }
        }
    );

};

/**
 * Registers generic Toolbar actions.
 */
const registerToolbarEvents = () => {
    if (toolbarEventsRegistered) {
        return;
    }

    document.addEventListener(
        'click',
        (event) => {
            const action =
                event.target.closest(
                    [
                        SELECTORS.toolbarSave,
                        SELECTORS.toolbarReset,
                        SELECTORS.toolbarCancel,
                    ].join(',')
                );

            if (!action) {
                return;
            }

            const toolbar =
                action.closest(
                    '[data-region="workspace-toolbar"]'
                );

            const workspaceKey =
                toolbar?.dataset.workspace || '';

            const root =
                findController(workspaceKey);

            if (!root) {
                return;
            }

            if (
                action.matches(
                    SELECTORS.toolbarSave
                )
            ) {
                save(root);
                return;
            }

            if (
                action.matches(
                    SELECTORS.toolbarReset
                )
            ) {
                reset(root);
                return;
            }

            if (
                action.matches(
                    SELECTORS.toolbarCancel
                )
            ) {
                closePanel(
                    root,
                    true,
                    'toolbar-cancel'
                );
            }
        }
    );

    toolbarEventsRegistered = true;
};

/**
 * Registers synchronization between the visible Workspace grid
 * and the Dashboard personalization panel.
 */
const registerWorkspaceOrderEvents = () => {
    if (workspaceOrderEventsRegistered) {
        return;
    }

    document.addEventListener(
        WORKSPACE_EVENTS.orderChanged,
        (event) => {
            const workspace =
                event.target instanceof HTMLElement
                    ? event.target.closest(
                        SELECTORS.workspace
                    )
                    : null;

            const workspaceKey =
                event.detail?.workspace
                || workspace?.dataset.workspace
                || '';

            if (
                !workspace
                || !workspaceKey
            ) {
                return;
            }

            const root =
                findController(workspaceKey);

            if (!root) {
                return;
            }

            synchronizePanelFromWorkspace(
                root,
                workspace
            );
        }
    );

    workspaceOrderEventsRegistered = true;
};

/**
 * Initializes all generic Workspace personalization controllers.
 */
export const init = () => {
    document.querySelectorAll(
        SELECTORS.root
    ).forEach((root) => {
        if (
            root.dataset.workspacePersonalizationInitialized
            === '1'
        ) {
            return;
        }

        root.dataset.workspacePersonalizationInitialized =
            '1';

        registerEvents(root);
    });

    registerToolbarEvents();
    registerWorkspaceOrderEvents();
};