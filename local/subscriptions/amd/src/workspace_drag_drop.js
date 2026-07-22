/* eslint-env amd */
/**
 * Generic CRM Workspace grid drag-and-drop engine.
 *
 * This module:
 * - activates dragging only during Workspace edit mode;
 * - allows movable items to be reordered inside their current zone;
 * - restores the initial grid order when editing is cancelled;
 * - emits generic Workspace events after a successful move.
 *
 * Cross-zone moves are intentionally disabled because the current
 * Workspace server-side layout model assigns each item to one fixed zone.
 *
 * @module local_subscriptions/workspace_drag_drop
 */

const SELECTORS = {
    workspace:
        '[data-region="crm-workspace"]',
    zone:
        '[data-region="workspace-zone"]',
    item:
        '[data-region="workspace-item"]',
    movableItem:
        '[data-region="workspace-item"]' +
        '[data-workspace-movable="1"]',
    handle:
        '[data-region="workspace-item-drag-handle"]',
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
};

const states = new Map();

const AUTO_SCROLL = {
    edge: 96,
    maximumStep: 22,
};

const DROP_INDICATOR = {
    before: 'is-workspace-drop-before',
    after: 'is-workspace-drop-after',
};

const MOVE_ANIMATION_CLASS =
    'is-workspace-item-just-moved';

let initialized = false;
let armedItem = null;
let draggedItem = null;
let indicatedItem = null;
let indicatedPosition = '';

/**
 * Dispatches one bubbling Workspace event.
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
 * Returns the stable Workspace key.
 *
 * @param {HTMLElement|null} element
 * @returns {string}
 */
const getWorkspaceKey = (element) => {
    return element?.dataset?.workspace || '';
};

/**
 * Returns the stable item key.
 *
 * @param {HTMLElement|null} item
 * @returns {string}
 */
const getItemKey = (item) => {
    return item?.dataset?.workspaceItem || '';
};

/**
 * Returns the stable zone key.
 *
 * @param {HTMLElement|null} zone
 * @returns {string}
 */
const getZoneKey = (zone) => {
    return zone?.dataset?.workspaceZone || '';
};

/**
 * Finds a registered Workspace state from an event.
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

    const key = getWorkspaceKey(workspace);

    return key
        ? states.get(key) || null
        : null;
};

/**
 * Captures the current order of rendered items in every zone.
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

        order[zoneKey] = [];

        zone.querySelectorAll(
            `:scope > ${SELECTORS.item}`
        ).forEach((item) => {
            const itemKey = getItemKey(item);

            if (itemKey) {
                order[zoneKey].push(itemKey);
            }
        });
    });

    return order;
};

/**
 * Restores a previously captured rendered-item order.
 *
 * @param {HTMLElement} workspace
 * @param {Object} order
 */
const restoreOrder = (
    workspace,
    order
) => {
    if (!order || typeof order !== 'object') {
        return;
    }

    workspace.querySelectorAll(
        SELECTORS.zone
    ).forEach((zone) => {
        const zoneKey = getZoneKey(zone);
        const zoneOrder = order[zoneKey];

        if (
            !zoneKey
            || !Array.isArray(zoneOrder)
        ) {
            return;
        }

        const items = new Map();

        zone.querySelectorAll(
            `:scope > ${SELECTORS.item}`
        ).forEach((item) => {
            const itemKey = getItemKey(item);

            if (itemKey) {
                items.set(itemKey, item);
            }
        });

        zoneOrder.forEach((itemKey) => {
            const item = items.get(itemKey);

            if (item) {
                zone.appendChild(item);
                items.delete(itemKey);
            }
        });

        /*
         * Any newly rendered or unknown item remains available and is
         * appended after the restored snapshot.
         */
        items.forEach((item) => {
            zone.appendChild(item);
        });
    });
};

/**
 * Creates one Workspace drag-and-drop state.
 *
 * @param {HTMLElement} workspace
 * @returns {Object}
 */
const createState = (workspace) => {
    return {
        workspace,
        workspaceKey:
            getWorkspaceKey(workspace),
        active: false,
        snapshot: {},
        sourceZone: null,
        sourceIndex: -1,
        moved: false,
    };
};

/**
 * Returns direct draggable items of one zone.
 *
 * @param {HTMLElement} zone
 * @param {HTMLElement|null} excludedItem
 * @returns {HTMLElement[]}
 */
const getZoneItems = (
    zone,
    excludedItem = null
) => {
    return [
        ...zone.querySelectorAll(
            `:scope > ${SELECTORS.movableItem}`
        ),
    ].filter((item) => {
        return item !== excludedItem;
    });
};

/**
 * Returns one item's current direct-child index.
 *
 * @param {HTMLElement} item
 * @returns {number}
 */
const getItemIndex = (item) => {
    if (!item.parentElement) {
        return -1;
    }

    return [
        ...item.parentElement.children,
    ].indexOf(item);
};

/**
 * Returns whether a pointer is visually before an item.
 *
 * This supports both vertical lists and responsive CSS grids.
 *
 * @param {DOMRect} rect
 * @param {number} clientX
 * @param {number} clientY
 * @returns {boolean}
 */
const isPointerBefore = (
    rect,
    clientX,
    clientY
) => {
    const verticalTolerance =
        Math.max(12, rect.height * 0.28);

    const centerX =
        rect.left + rect.width / 2;

    const centerY =
        rect.top + rect.height / 2;

    if (
        clientY
        < centerY - verticalTolerance
    ) {
        return true;
    }

    if (
        Math.abs(clientY - centerY)
        <= verticalTolerance
    ) {
        return clientX < centerX;
    }

    return false;
};

/**
 * Returns the visual insertion position relative to an item.
 *
 * @param {HTMLElement|null} target
 * @param {number} clientX
 * @param {number} clientY
 * @returns {string}
 */
const getIndicatorPosition = (
    target,
    clientX,
    clientY
) => {
    if (!target) {
        return '';
    }

    return isPointerBefore(
        target.getBoundingClientRect(),
        clientX,
        clientY
    )
        ? DROP_INDICATOR.before
        : DROP_INDICATOR.after;
};

/**
 * Finds the item before which the dragged item should be inserted.
 *
 * @param {HTMLElement} zone
 * @param {HTMLElement} currentItem
 * @param {number} clientX
 * @param {number} clientY
 * @returns {HTMLElement|null}
 */
const findInsertionTarget = (
    zone,
    currentItem,
    clientX,
    clientY
) => {
    const candidates = getZoneItems(
        zone,
        currentItem
    );

    const measured = candidates.map(
        (item) => {
            return {
                item,
                rect:
                    item.getBoundingClientRect(),
            };
        }
    );

    measured.sort((first, second) => {
        const topDifference =
            first.rect.top - second.rect.top;

        if (Math.abs(topDifference) > 8) {
            return topDifference;
        }

        return first.rect.left
            - second.rect.left;
    });

    const match = measured.find(
        ({rect}) => {
            return isPointerBefore(
                rect,
                clientX,
                clientY
            );
        }
    );

    return match?.item || null;
};

/**
 * Clears the current visual insertion indicator.
 */
const clearDropIndicator = () => {
    if (!indicatedItem) {
        indicatedPosition = '';
        return;
    }

    indicatedItem.classList.remove(
        DROP_INDICATOR.before,
        DROP_INDICATOR.after
    );

    indicatedItem = null;
    indicatedPosition = '';
};

/**
 * Displays the current insertion position.
 *
 * @param {HTMLElement|null} target
 * @param {string} position
 */
const setDropIndicator = (
    target,
    position
) => {
    if (
        indicatedItem === target
        && indicatedPosition === position
    ) {
        return;
    }

    clearDropIndicator();

    if (
        !target
        || !Object.values(
            DROP_INDICATOR
        ).includes(position)
    ) {
        return;
    }

    indicatedItem = target;
    indicatedPosition = position;

    target.classList.add(position);
};

/**
 * Scrolls the page when dragging close to a viewport edge.
 *
 * @param {number} clientY
 */
const autoScrollViewport = (clientY) => {
    const viewportHeight =
        window.innerHeight
        || document.documentElement.clientHeight;

    if (viewportHeight <= 0) {
        return;
    }

    let distance = 0;

    if (clientY < AUTO_SCROLL.edge) {
        const ratio =
            1 - Math.max(
                clientY,
                0
            ) / AUTO_SCROLL.edge;

        distance =
            -Math.ceil(
                AUTO_SCROLL.maximumStep
                * ratio
            );
    } else if (
        clientY
        > viewportHeight - AUTO_SCROLL.edge
    ) {
        const ratio =
            1 - Math.max(
                viewportHeight - clientY,
                0
            ) / AUTO_SCROLL.edge;

        distance =
            Math.ceil(
                AUTO_SCROLL.maximumStep
                * ratio
            );
    }

    if (distance !== 0) {
        window.scrollBy({
            top: distance,
            left: 0,
            behavior: 'auto',
        });
    }
};

/**
 * Updates direct-child ordering according to pointer position.
 *
 * @param {Object} state
 * @param {HTMLElement} zone
 * @param {number} clientX
 * @param {number} clientY
 */
const moveDraggedItem = (
    state,
    zone,
    clientX,
    clientY
) => {
    if (
        !draggedItem
        || draggedItem.parentElement !== zone
    ) {
        clearDropIndicator();
        return;
    }

    const previousIndex =
        getItemIndex(draggedItem);

    const target = findInsertionTarget(
        zone,
        draggedItem,
        clientX,
        clientY
    );

    if (target === null) {
        const remainingItems =
            getZoneItems(
                zone,
                draggedItem
            );

        const lastItem =
            remainingItems[
                remainingItems.length - 1
            ] || null;

        if (lastItem) {
            setDropIndicator(
                lastItem,
                DROP_INDICATOR.after
            );
        } else {
            clearDropIndicator();
        }

        zone.appendChild(draggedItem);
    } else {
        setDropIndicator(
            target,
            getIndicatorPosition(
                target,
                clientX,
                clientY
            )
        );

        zone.insertBefore(
            draggedItem,
            target
        );
    }

    const nextIndex =
        getItemIndex(draggedItem);

    if (
        previousIndex >= 0
        && nextIndex >= 0
        && previousIndex !== nextIndex
    ) {
        state.moved = true;
    }
};

/**
 * Briefly highlights an item after a successful move.
 *
 * @param {HTMLElement} item
 */
const animateMovedItem = (item) => {
    item.classList.remove(
        MOVE_ANIMATION_CLASS
    );

    window.requestAnimationFrame(() => {
        item.classList.add(
            MOVE_ANIMATION_CLASS
        );

        window.setTimeout(() => {
            item.classList.remove(
                MOVE_ANIMATION_CLASS
            );
        }, 420);
    });
};

/**
 * Removes all temporary drag visual states.
 *
 * @param {Object} state
 */
const clearDragState = (state) => {
    state.workspace.classList.remove(
        'is-workspace-dragging'
    );

    clearDropIndicator();

    state.workspace.querySelectorAll(
        SELECTORS.zone
    ).forEach((zone) => {
        zone.classList.remove(
            'is-workspace-drop-zone',
            'is-workspace-drop-zone-active'
        );

        zone.removeAttribute(
            'aria-dropeffect'
        );
    });

    if (draggedItem) {
        draggedItem.classList.remove(
            'is-workspace-item-dragging'
        );

        draggedItem.setAttribute(
            'aria-grabbed',
            'false'
        );
    }

    if (armedItem) {
        armedItem.classList.remove(
            'is-workspace-item-drag-armed'
        );
    }

    armedItem = null;
    draggedItem = null;

    state.sourceZone = null;
    state.sourceIndex = -1;
};

/**
 * Enables or disables native draggable behavior.
 *
 * @param {Object} state
 * @param {boolean} active
 */
const setDraggableState = (
    state,
    active
) => {
    state.workspace.querySelectorAll(
        SELECTORS.movableItem
    ).forEach((item) => {
        item.draggable = active;

        item.setAttribute(
            'aria-grabbed',
            'false'
        );

        item.dataset.workspaceDraggable =
            active ? '1' : '0';
    });
};

/**
 * Activates drag-and-drop for one Workspace.
 *
 * @param {Object} state
 */
const activate = (state) => {
    if (state.active) {
        return;
    }

    state.active = true;
    state.snapshot = captureOrder(
        state.workspace
    );

    setDraggableState(
        state,
        true
    );

    state.workspace.classList.add(
        'is-workspace-drag-enabled'
    );
};

/**
 * Deactivates drag-and-drop for one Workspace.
 *
 * @param {Object} state
 * @param {boolean} restore
 */
const deactivate = (
    state,
    restore
) => {
    if (!state.active) {
        return;
    }

    clearDragState(state);

    if (restore) {
        restoreOrder(
            state.workspace,
            state.snapshot
        );
    }

    setDraggableState(
        state,
        false
    );

    state.workspace.classList.remove(
        'is-workspace-drag-enabled'
    );

    state.active = false;
    state.snapshot = {};
    state.moved = false;
};

/**
 * Handles pointer preparation on a drag handle.
 *
 * @param {PointerEvent} event
 */
const handlePointerDown = (event) => {
    const handle =
        event.target.closest(
            SELECTORS.handle
        );

    if (!handle) {
        return;
    }

    const item = handle.closest(
        SELECTORS.movableItem
    );

    const workspace = handle.closest(
        SELECTORS.workspace
    );

    const state = states.get(
        getWorkspaceKey(workspace)
    );

    if (
        !item
        || !state
        || !state.active
    ) {
        return;
    }

    armedItem = item;

    item.classList.add(
        'is-workspace-item-drag-armed'
    );
};

/**
 * Clears a prepared handle when no drag started.
 */
const handlePointerUp = () => {
    if (
        armedItem
        && armedItem !== draggedItem
    ) {
        armedItem.classList.remove(
            'is-workspace-item-drag-armed'
        );

        armedItem = null;
    }
};

/**
 * Starts one drag operation.
 *
 * @param {DragEvent} event
 */
const handleDragStart = (event) => {
    const item =
        event.target.closest(
            SELECTORS.movableItem
        );

    const workspace = item?.closest(
        SELECTORS.workspace
    );

    const state = states.get(
        getWorkspaceKey(workspace)
    );

    if (
        !item
        || !state
        || !state.active
        || armedItem !== item
    ) {
        event.preventDefault();
        return;
    }

    const sourceZone = item.closest(
        SELECTORS.zone
    );

    if (!sourceZone) {
        event.preventDefault();
        return;
    }

    draggedItem = item;
    state.sourceZone = sourceZone;
    state.sourceIndex =
        getItemIndex(item);
    state.moved = false;

    item.classList.remove(
        'is-workspace-item-drag-armed'
    );

    item.classList.add(
        'is-workspace-item-dragging'
    );

    item.setAttribute(
        'aria-grabbed',
        'true'
    );

    workspace.classList.add(
        'is-workspace-dragging'
    );

    sourceZone.classList.add(
        'is-workspace-drop-zone',
        'is-workspace-drop-zone-active'
    );

    sourceZone.setAttribute(
        'aria-dropeffect',
        'move'
    );

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed =
            'move';

        event.dataTransfer.dropEffect =
            'move';

        event.dataTransfer.setData(
            'text/plain',
            getItemKey(item)
        );
    }
};

/**
 * Handles movement inside the item's original zone.
 *
 * @param {DragEvent} event
 */
const handleDragOver = (event) => {
    if (!draggedItem) {
        return;
    }

    const zone =
        event.target.closest(
            SELECTORS.zone
        );

    const workspace =
        draggedItem.closest(
            SELECTORS.workspace
        );

    const state = states.get(
        getWorkspaceKey(workspace)
    );

    if (
        !zone
        || !state
        || zone !== state.sourceZone
    ) {
        return;
    }

    event.preventDefault();

    if (event.dataTransfer) {
        event.dataTransfer.dropEffect =
            'move';
    }

    autoScrollViewport(
        event.clientY
    );

    moveDraggedItem(
        state,
        zone,
        event.clientX,
        event.clientY
    );
};

/**
 * Accepts the current item position.
 *
 * @param {DragEvent} event
 */
const handleDrop = (event) => {
    if (!draggedItem) {
        return;
    }

    const zone =
        event.target.closest(
            SELECTORS.zone
        );

    const workspace =
        draggedItem.closest(
            SELECTORS.workspace
        );

    const state = states.get(
        getWorkspaceKey(workspace)
    );

    if (
        !zone
        || !state
        || zone !== state.sourceZone
    ) {
        return;
    }

    event.preventDefault();
};

/**
 * Finishes one drag operation.
 */
const handleDragEnd = () => {
    if (!draggedItem) {
        return;
    }

    const workspace =
        draggedItem.closest(
            SELECTORS.workspace
        );

    const state = states.get(
        getWorkspaceKey(workspace)
    );

    if (!state) {
        draggedItem = null;
        armedItem = null;
        return;
    }

    const itemKey =
        getItemKey(draggedItem);

    const zoneKey =
        getZoneKey(state.sourceZone);

    const finalIndex =
        getItemIndex(draggedItem);

    const movedItem = draggedItem;

    const changed =
        state.moved
        && state.sourceIndex >= 0
        && finalIndex >= 0
        && state.sourceIndex !== finalIndex;

    clearDragState(state);

    if (!changed) {
        return;
    }

    animateMovedItem(movedItem);

    dispatch(
        state.workspace,
        EVENTS.orderChanged,
        {
            workspace:
                state.workspaceKey,
            item: itemKey,
            zone: zoneKey,
            index: finalIndex,
            order: captureOrder(
                state.workspace
            ),
            source: 'drag-drop',
        }
    );

    dispatch(
        state.workspace,
        EVENTS.changed,
        {
            workspace:
                state.workspaceKey,
            source:
                'drag-drop',
        }
    );
};

/**
 * Clears temporary states if the browser interrupts the drag.
 */
const handleWindowBlur = () => {
    if (!draggedItem && !armedItem) {
        return;
    }

    const workspace =
        draggedItem?.closest(
            SELECTORS.workspace
        )
        || armedItem?.closest(
            SELECTORS.workspace
        );

    const state = states.get(
        getWorkspaceKey(workspace)
    );

    if (state) {
        clearDragState(state);
    } else {
        clearDropIndicator();

        armedItem = null;
        draggedItem = null;
    }
};

/**
 * Registers document-level drag interactions.
 */
const registerDragEvents = () => {
    document.addEventListener(
        'pointerdown',
        handlePointerDown
    );

    document.addEventListener(
        'pointerup',
        handlePointerUp
    );

    document.addEventListener(
        'pointercancel',
        handlePointerUp
    );

    document.addEventListener(
        'dragstart',
        handleDragStart
    );

    document.addEventListener(
        'dragover',
        handleDragOver
    );

    document.addEventListener(
        'drop',
        handleDrop
    );

    document.addEventListener(
        'dragend',
        handleDragEnd
    );

    window.addEventListener(
        'blur',
        handleWindowBlur
    );    
};

/**
 * Registers Workspace edit-mode lifecycle events.
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
 * Registers all rendered CRM Workspaces.
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
            createState(workspace)
        );

        /*
         * Native dragging must never remain active outside edit mode,
         * including after browser history restoration.
         */
        workspace.querySelectorAll(
            SELECTORS.movableItem
        ).forEach((item) => {
            item.draggable = false;

            item.setAttribute(
                'aria-grabbed',
                'false'
            );

            item.dataset.workspaceDraggable =
                '0';
        });
    });
};

/**
 * Initializes the generic Workspace drag-and-drop engine.
 */
export const init = () => {
    initializeStates();

    if (initialized) {
        return;
    }

    registerWorkspaceEvents();
    registerDragEvents();

    initialized = true;
};