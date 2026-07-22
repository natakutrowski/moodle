/* eslint-env amd */
/**
 * Generic contextual menu for CRM Workspace items.
 *
 * @module local_subscriptions/workspace_item_menu
 */

const SELECTORS = {
    workspace:
        '[data-region="crm-workspace"]',
    zone:
        '[data-region="workspace-zone"]',
    item:
        '[data-region="workspace-item"]',
    controller:
        '[data-region="workspace-edit-controller"]',
    panelCard:
        '[data-card-key]',
    visibility:
        '.crm-dashboard-card-visibility',
    wrapper:
        '[data-region="workspace-item-menu-wrapper"]',
    menu:
        '[data-region="workspace-item-menu"]',
    trigger:
        '[data-action="workspace-item-menu-toggle"]',
    menuAction:
        '[data-region="workspace-item-menu"] ' +
        '[data-action]',
    moveBefore:
        '[data-action="workspace-item-move-before"]',
    moveAfter:
        '[data-action="workspace-item-move-after"]',
    hide:
        '[data-action="workspace-item-hide"]',
    reset:
        '[data-action="workspace-item-reset"]',
    customAction:
        '[data-action="workspace-item-custom-action"]',
};

const EVENTS = {
    entered:
        'local-subscriptions:workspace-edit-entered',
    exited:
        'local-subscriptions:workspace-edit-exited',
    changed:
        'local-subscriptions:workspace-edit-changed',
    orderChanged:
        'local-subscriptions:workspace-order-changed',
    visibilityChanged:
        'local-subscriptions:workspace-item-visibility-changed',
    customAction:
        'local-subscriptions:workspace-item-action',
    reset:
        'local-subscriptions:workspace-item-reset',
};

const states = new Map();

let initialized = false;
let openWrapper = null;
let openTrigger = null;

/**
 * Dispatches a bubbling Workspace event.
 *
 * @param {HTMLElement} target
 * @param {string} name
 * @param {Object} detail
 */
const dispatch = (
    target,
    name,
    detail = {}
) => {
    target.dispatchEvent(
        new CustomEvent(name, {
            bubbles: true,
            detail,
        })
    );
};

/**
 * Returns a Workspace key.
 *
 * @param {HTMLElement|null} element
 * @returns {string}
 */
const getWorkspaceKey = (element) => {
    return element?.dataset?.workspace || '';
};

/**
 * Returns an item's stable key.
 *
 * @param {HTMLElement|null} item
 * @returns {string}
 */
const getItemKey = (item) => {
    return item?.dataset?.workspaceItem || '';
};

/**
 * Returns a zone's stable key.
 *
 * @param {HTMLElement|null} zone
 * @returns {string}
 */
const getZoneKey = (zone) => {
    return zone?.dataset?.workspaceZone || '';
};

/**
 * Returns direct Workspace items belonging to one zone.
 *
 * @param {HTMLElement} zone
 * @returns {HTMLElement[]}
 */
const getZoneItems = (zone) => {
    return [
        ...zone.querySelectorAll(
            `:scope > ${SELECTORS.item}`
        ),
    ];
};

/**
 * Captures item visibility for cancellation.
 *
 * @param {HTMLElement} workspace
 * @returns {Object}
 */
const captureVisibility = (workspace) => {
    const visibility = {};

    workspace.querySelectorAll(
        SELECTORS.item
    ).forEach((item) => {
        const itemKey = getItemKey(item);

        if (itemKey) {
            visibility[itemKey] =
                item.hidden;
        }
    });

    return visibility;
};

/**
 * Captures each rendered item's initial edit-session state.
 *
 * @param {HTMLElement} workspace
 * @returns {Object}
 */
const captureItemStates = (workspace) => {
    const itemStates = {};

    workspace.querySelectorAll(
        SELECTORS.zone
    ).forEach((zone) => {
        const zoneKey = getZoneKey(zone);

        getZoneItems(zone).forEach(
            (item, index) => {
                const itemKey =
                    getItemKey(item);

                if (!itemKey) {
                    return;
                }

                itemStates[itemKey] = {
                    zone: zoneKey,
                    index,
                    hidden: item.hidden,
                };
            }
        );
    });

    return itemStates;
};

/**
 * Restores item visibility after cancellation.
 *
 * @param {HTMLElement} workspace
 * @param {Object} visibility
 */
const restoreVisibility = (
    workspace,
    visibility
) => {
    if (
        !visibility
        || typeof visibility !== 'object'
    ) {
        return;
    }

    workspace.querySelectorAll(
        SELECTORS.item
    ).forEach((item) => {
        const itemKey = getItemKey(item);

        if (
            itemKey
            && Object.prototype.hasOwnProperty.call(
                visibility,
                itemKey
            )
        ) {
            item.hidden =
                visibility[itemKey] === true;
        }
    });
};

/**
 * Captures the current order of each Workspace zone.
 *
 * @param {HTMLElement} workspace
 * @returns {Object}
 */
const captureOrder = (workspace) => {
    const order = {};

    workspace.querySelectorAll(
        SELECTORS.zone
    ).forEach((zone) => {
        const zoneKey = getZoneKey(zone);

        if (!zoneKey) {
            return;
        }

        order[zoneKey] = getZoneItems(
            zone
        ).map((item) => {
            return getItemKey(item);
        }).filter(Boolean);
    });

    return order;
};

/**
 * Resolves the contextual-menu state from a Workspace event.
 *
 * @param {Event} event
 * @returns {Object|null}
 */
const findStateFromEvent = (event) => {
    const workspaceKey =
        event.detail?.workspace || '';

    if (workspaceKey) {
        return states.get(workspaceKey) || null;
    }

    const source =
        event.target instanceof HTMLElement
            ? event.target
            : null;

    const workspace = source?.closest(
        SELECTORS.workspace
    );

    const key =
        getWorkspaceKey(workspace);

    return key
        ? states.get(key) || null
        : null;
};

/**
 * Closes the currently open contextual menu.
 *
 * @param {boolean} restoreFocus
 */
const closeMenu = (
    restoreFocus = false
) => {
    if (!openWrapper) {
        return;
    }

    const menu = openWrapper.querySelector(
        SELECTORS.menu
    );

    const trigger = openWrapper.querySelector(
        SELECTORS.trigger
    );

    if (menu) {
        menu.hidden = true;
    }

    openWrapper.classList.remove(
        'is-workspace-item-menu-open'
    );

    if (trigger) {
        trigger.setAttribute(
            'aria-expanded',
            'false'
        );
    }

    const focusTarget =
        restoreFocus
            ? openTrigger || trigger
            : null;

    openWrapper = null;
    openTrigger = null;

    if (
        focusTarget
        && document.contains(focusTarget)
    ) {
        focusTarget.focus({
            preventScroll: true,
        });
    }
};

/**
 * Returns focusable actions from one menu.
 *
 * @param {HTMLElement} menu
 * @returns {HTMLElement[]}
 */
const getMenuActions = (menu) => {
    return [
        ...menu.querySelectorAll(
            SELECTORS.menuAction
        ),
    ].filter((action) => {
        return !action.disabled
            && !action.hidden;
    });
};

/**
 * Enables or disables movement actions according to item position.
 *
 * @param {HTMLElement} wrapper
 * @param {HTMLElement} item
 */
const updateMovementActions = (
    wrapper,
    item
) => {
    const zone = item.closest(
        SELECTORS.zone
    );

    if (!zone) {
        return;
    }

    const items = getZoneItems(zone);
    const index = items.indexOf(item);

    const moveBefore =
        wrapper.querySelector(
            SELECTORS.moveBefore
        );

    const moveAfter =
        wrapper.querySelector(
            SELECTORS.moveAfter
        );

    if (moveBefore) {
        moveBefore.disabled =
            index <= 0;

        moveBefore.setAttribute(
            'aria-disabled',
            index <= 0 ? 'true' : 'false'
        );
    }

    if (moveAfter) {
        moveAfter.disabled =
            index < 0
            || index >= items.length - 1;

        moveAfter.setAttribute(
            'aria-disabled',
            index < 0
            || index >= items.length - 1
                ? 'true'
                : 'false'
        );
    }
};

/**
 * Opens one contextual menu.
 *
 * @param {HTMLElement} wrapper
 * @param {HTMLElement} trigger
 */
const openMenu = (
    wrapper,
    trigger
) => {
    if (
        openWrapper
        && openWrapper !== wrapper
    ) {
        closeMenu(false);
    }

    const item = wrapper.closest(
        SELECTORS.item
    );

    const workspace = wrapper.closest(
        SELECTORS.workspace
    );

    if (
        !item
        || !workspace
        || workspace.dataset.workspaceEditing
            !== '1'
    ) {
        return;
    }

    const menu = wrapper.querySelector(
        SELECTORS.menu
    );

    if (!menu) {
        return;
    }

    updateMovementActions(
        wrapper,
        item
    );

    menu.hidden = false;

    wrapper.classList.add(
        'is-workspace-item-menu-open'
    );

    trigger.setAttribute(
        'aria-expanded',
        'true'
    );

    openWrapper = wrapper;
    openTrigger = trigger;

    const firstAction =
        getMenuActions(menu)[0];

    if (firstAction) {
        firstAction.focus({
            preventScroll: true,
        });
    }
};

/**
 * Toggles one contextual menu.
 *
 * @param {HTMLElement} trigger
 */
const toggleMenu = (trigger) => {
    const wrapper = trigger.closest(
        SELECTORS.wrapper
    );

    if (!wrapper) {
        return;
    }

    if (openWrapper === wrapper) {
        closeMenu(true);
        return;
    }

    openMenu(
        wrapper,
        trigger
    );
};

/**
 * Synchronizes one Workspace item's visibility with the Dashboard panel.
 *
 * @param {HTMLElement} workspace
 * @param {string} itemKey
 * @param {boolean} visible
 */
const synchronizePanelVisibility = (
    workspace,
    itemKey,
    visible
) => {
    const workspaceKey =
        getWorkspaceKey(workspace);

    if (
        !workspaceKey
        || !itemKey
    ) {
        return;
    }

    const controllers = [
        ...document.querySelectorAll(
            SELECTORS.controller
        ),
    ];

    const controller = controllers.find(
        (candidate) => {
            return getWorkspaceKey(candidate)
                === workspaceKey;
        }
    );

    if (!controller) {
        return;
    }

    const panelCards = [
        ...controller.querySelectorAll(
            SELECTORS.panelCard
        ),
    ];

    const panelCard = panelCards.find(
        (candidate) => {
            return candidate.dataset.cardKey
                === itemKey;
        }
    );

    if (!panelCard) {
        return;
    }

    const visibility =
        panelCard.querySelector(
            SELECTORS.visibility
        );

    if (
        !visibility
        || !(visibility instanceof HTMLInputElement)
    ) {
        return;
    }

    if (visibility.checked === visible) {
        return;
    }

    visibility.checked = visible;

    visibility.dispatchEvent(
        new Event('change', {
            bubbles: true,
        })
    );
};

/**
 * Notifies listeners after a DOM-order change.
 *
 * @param {HTMLElement} workspace
 * @param {HTMLElement} item
 * @param {string} source
 */
const notifyOrderChanged = (
    workspace,
    item,
    source
) => {
    const zone = item.closest(
        SELECTORS.zone
    );

    if (!zone) {
        return;
    }

    const items = getZoneItems(zone);
    const itemKey = getItemKey(item);

    dispatch(
        workspace,
        EVENTS.orderChanged,
        {
            workspace:
                getWorkspaceKey(workspace),
            item: itemKey,
            zone: getZoneKey(zone),
            index: items.indexOf(item),
            order: captureOrder(workspace),
            source,
        }
    );

    dispatch(
        workspace,
        EVENTS.changed,
        {
            workspace:
                getWorkspaceKey(workspace),
            item: itemKey,
            source,
        }
    );
};

/**
 * Moves an item before its previous sibling.
 *
 * @param {HTMLElement} workspace
 * @param {HTMLElement} item
 */
const moveBefore = (
    workspace,
    item
) => {
    const zone = item.closest(
        SELECTORS.zone
    );

    if (!zone) {
        return;
    }

    const items = getZoneItems(zone);
    const index = items.indexOf(item);

    if (index <= 0) {
        return;
    }

    zone.insertBefore(
        item,
        items[index - 1]
    );

    notifyOrderChanged(
        workspace,
        item,
        'context-menu-move-before'
    );
};

/**
 * Moves an item after its next sibling.
 *
 * @param {HTMLElement} workspace
 * @param {HTMLElement} item
 */
const moveAfter = (
    workspace,
    item
) => {
    const zone = item.closest(
        SELECTORS.zone
    );

    if (!zone) {
        return;
    }

    const items = getZoneItems(zone);
    const index = items.indexOf(item);

    if (
        index < 0
        || index >= items.length - 1
    ) {
        return;
    }

    const nextItem = items[index + 1];

    zone.insertBefore(
        item,
        nextItem.nextElementSibling
    );

    notifyOrderChanged(
        workspace,
        item,
        'context-menu-move-after'
    );
};

/**
 * Hides one item from the current draft layout.
 *
 * @param {HTMLElement} workspace
 * @param {HTMLElement} item
 */
const hideItem = (
    workspace,
    item
) => {
    if (
        item.dataset.workspaceHideable
        !== '1'
    ) {
        return;
    }

    const itemKey = getItemKey(item);

    closeMenu(false);

    item.hidden = true;

    synchronizePanelVisibility(
        workspace,
        itemKey,
        false
    );

    dispatch(
        workspace,
        EVENTS.visibilityChanged,
        {
            workspace:
                getWorkspaceKey(workspace),
            item: itemKey,
            visible: false,
            source: 'context-menu-hide',
        }
    );

    dispatch(
        workspace,
        EVENTS.changed,
        {
            workspace:
                getWorkspaceKey(workspace),
            item: itemKey,
            source: 'context-menu-hide',
        }
    );
};

/**
 * Restores one item to its state at the beginning of edit mode.
 *
 * @param {HTMLElement} workspace
 * @param {HTMLElement} item
 */
const resetItem = (
    workspace,
    item
) => {
    const workspaceKey =
        getWorkspaceKey(workspace);

    const state =
        states.get(workspaceKey);

    const itemKey =
        getItemKey(item);

    const initial =
        state?.itemStates?.[itemKey];

    if (!state || !initial) {
        return;
    }

    const targetZone = [
        ...workspace.querySelectorAll(
            SELECTORS.zone
        ),
    ].find((zone) => {
        return getZoneKey(zone)
            === initial.zone;
    });

    if (!targetZone) {
        return;
    }

    closeMenu(false);

    const siblings = getZoneItems(
        targetZone
    ).filter((candidate) => {
        return candidate !== item;
    });

    const target =
        siblings[initial.index] || null;

    if (target) {
        targetZone.insertBefore(
            item,
            target
        );
    } else {
        targetZone.appendChild(item);
    }

    item.hidden =
        initial.hidden === true;

    synchronizePanelVisibility(
        workspace,
        itemKey,
        !item.hidden
    );

    notifyOrderChanged(
        workspace,
        item,
        'context-menu-reset'
    );

    dispatch(
        workspace,
        EVENTS.reset,
        {
            workspace: workspaceKey,
            item: itemKey,
            zone: initial.zone,
            index: initial.index,
            visible: !item.hidden,
        }
    );
};

/**
 * Dispatches one module-specific Workspace item action.
 *
 * @param {HTMLElement} workspace
 * @param {HTMLElement} item
 * @param {HTMLElement} action
 */
const executeCustomAction = (
    workspace,
    item,
    action
) => {
    const actionKey =
        action.dataset.workspaceCustomAction
        || '';

    if (!actionKey) {
        return;
    }

    closeMenu(false);

    dispatch(
        workspace,
        EVENTS.customAction,
        {
            workspace:
                getWorkspaceKey(workspace),
            item:
                getItemKey(item),
            itemType:
                item.dataset.workspaceItemType
                || '',
            action: actionKey,
            source: 'context-menu-custom',
        }
    );
};

/**
 * Executes one contextual-menu action.
 *
 * @param {HTMLElement} action
 */
const executeAction = (action) => {
    if (action.disabled) {
        return;
    }

    const item = action.closest(
        SELECTORS.item
    );

    const workspace = action.closest(
        SELECTORS.workspace
    );

    if (
        !item
        || !workspace
        || workspace.dataset.workspaceEditing
            !== '1'
    ) {
        return;
    }

    if (action.matches(SELECTORS.moveBefore)) {
        closeMenu(false);
        moveBefore(workspace, item);
        return;
    }

    if (action.matches(SELECTORS.moveAfter)) {
        closeMenu(false);
        moveAfter(workspace, item);
        return;
    }

    if (action.matches(SELECTORS.hide)) {
        hideItem(workspace, item);
        return;
    }

    if (action.matches(SELECTORS.reset)) {
        resetItem(workspace, item);
        return;
    }

    if (
        action.matches(
            SELECTORS.customAction
        )
    ) {
        executeCustomAction(
            workspace,
            item,
            action
        );
    }
};

/**
 * Handles contextual-menu clicks.
 *
 * @param {MouseEvent} event
 */
const handleClick = (event) => {
    const trigger = event.target.closest(
        SELECTORS.trigger
    );

    if (trigger) {
        event.preventDefault();
        event.stopPropagation();

        toggleMenu(trigger);
        return;
    }

    const action = event.target.closest(
        SELECTORS.menuAction
    );

    if (action) {
        event.preventDefault();
        event.stopPropagation();

        executeAction(action);
        return;
    }

    if (
        openWrapper
        && !openWrapper.contains(event.target)
    ) {
        closeMenu(false);
    }
};

/**
 * Moves keyboard focus inside an open menu.
 *
 * @param {KeyboardEvent} event
 * @param {HTMLElement} menu
 */
const moveMenuFocus = (
    event,
    menu
) => {
    const actions = getMenuActions(menu);

    if (actions.length === 0) {
        return;
    }

    const currentIndex =
        actions.indexOf(document.activeElement);

    let nextIndex = currentIndex;

    if (event.key === 'ArrowDown') {
        nextIndex =
            currentIndex < actions.length - 1
                ? currentIndex + 1
                : 0;
    } else if (event.key === 'ArrowUp') {
        nextIndex =
            currentIndex > 0
                ? currentIndex - 1
                : actions.length - 1;
    } else if (event.key === 'Home') {
        nextIndex = 0;
    } else if (event.key === 'End') {
        nextIndex = actions.length - 1;
    } else {
        return;
    }

    event.preventDefault();

    actions[nextIndex].focus({
        preventScroll: true,
    });
};

/**
 * Handles keyboard interaction.
 *
 * @param {KeyboardEvent} event
 */
const handleKeyDown = (event) => {
    if (event.key === 'Escape' && openWrapper) {
        event.preventDefault();
        event.stopPropagation();

        closeMenu(true);
        return;
    }

    const trigger = event.target.closest(
        SELECTORS.trigger
    );

    if (
        trigger
        && (
            event.key === 'ArrowDown'
            || event.key === 'Enter'
            || event.key === ' '
        )
    ) {
        event.preventDefault();

        openMenu(
            trigger.closest(SELECTORS.wrapper),
            trigger
        );

        return;
    }

    const menu = event.target.closest(
        SELECTORS.menu
    );

    if (menu) {
        moveMenuFocus(
            event,
            menu
        );
    }
};

/**
 * Activates contextual menus for one Workspace.
 *
 * @param {Object} state
 */
const activate = (state) => {
    if (state.active) {
        return;
    }

    state.active = true;

    state.visibilitySnapshot =
        captureVisibility(
            state.workspace
        );

    state.itemStates =
        captureItemStates(
            state.workspace
        );
};

/**
 * Deactivates contextual menus for one Workspace.
 *
 * @param {Object} state
 * @param {boolean} restore
 */
const deactivate = (
    state,
    restore
) => {
    closeMenu(false);

    if (
        restore
        && state.visibilitySnapshot
    ) {
        restoreVisibility(
            state.workspace,
            state.visibilitySnapshot
        );
    }

    state.active = false;
    state.visibilitySnapshot = {};
    state.itemStates = {};
};

/**
 * Registers Workspace lifecycle events.
 */
const registerWorkspaceEvents = () => {
    document.addEventListener(
        EVENTS.entered,
        (event) => {
            const state =
                findStateFromEvent(event);

            if (state) {
                activate(state);
            }
        }
    );

    document.addEventListener(
        EVENTS.exited,
        (event) => {
            const state =
                findStateFromEvent(event);

            if (!state) {
                return;
            }

            deactivate(
                state,
                event.detail?.restored === true
            );
        }
    );
};

/**
 * Registers all rendered Workspaces.
 */
const initializeStates = () => {
    document.querySelectorAll(
        SELECTORS.workspace
    ).forEach((workspace) => {
        const workspaceKey =
            getWorkspaceKey(workspace);

        if (
            !workspaceKey
            || states.has(workspaceKey)
        ) {
            return;
        }

        states.set(
            workspaceKey,
            {
                workspace,
                workspaceKey,
                active: false,
                visibilitySnapshot: {},
                itemStates: {},
            }
        );
    });
};

/**
 * Initializes Workspace contextual menus.
 */
export const init = () => {
    initializeStates();

    if (initialized) {
        return;
    }

    document.addEventListener(
        'click',
        handleClick
    );

    document.addEventListener(
        'keydown',
        handleKeyDown
    );

    registerWorkspaceEvents();

    initialized = true;
};