/* eslint-env amd */
/**
 * Generic CRM Workspace edit-mode engine.
 *
 * @module local_subscriptions/workspace_edit_mode
 */

const SELECTORS = {
    workspace: '[data-region="crm-workspace"]',
    item:
        '[data-region="workspace-item"]',
    itemEditChrome:
        '[data-region="workspace-item-edit-chrome"]',
    controller: '[data-region="workspace-edit-controller"]',
    panel: '[data-region="dashboard-personalization-panel"]',
    toolbar: '[data-region="workspace-toolbar"]',
    toolbarStatus:
        '[data-region="workspace-toolbar-status"]',
    toolbarHiddenCount:
        '[data-region="workspace-toolbar-hidden-count"]',
    toolbarHiddenLabel:
        '[data-region="workspace-toolbar-hidden-label"]',
    toolbarSave:
        '[data-action="workspace-toolbar-save"]',
    toolbarReset:
        '[data-action="workspace-toolbar-reset"]',
    toolbarCancel:
        '[data-action="workspace-toolbar-cancel"]',
    visibility:
        '.crm-dashboard-card-visibility',
};

const EVENTS = {
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
    entered:
        'local-subscriptions:workspace-edit-entered',
    exited:
        'local-subscriptions:workspace-edit-exited',
    stateChanged:
        'local-subscriptions:workspace-edit-state-changed',
};

const controllers = new Map();

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
 * Dispatches a Workspace event.
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
 * Captures the editable panel HTML.
 *
 * @param {HTMLElement} controller
 * @returns {string}
 */
const capturePanelSnapshot = (controller) => {
    const panel = controller.querySelector(
        SELECTORS.panel
    );

    return panel ? panel.innerHTML : '';
};

/**
 * Restores the editable panel HTML.
 *
 * @param {HTMLElement} controller
 * @param {string} snapshot
 */
const restorePanelSnapshot = (
    controller,
    snapshot
) => {
    const panel = controller.querySelector(
        SELECTORS.panel
    );

    if (
        !panel
        || typeof snapshot !== 'string'
    ) {
        return;
    }

    panel.innerHTML = snapshot;
};

/**
 * Counts currently hidden items.
 *
 * @param {HTMLElement} controller
 * @returns {number}
 */
const countHiddenItems = (controller) => {
    return controller.querySelectorAll(
        `${SELECTORS.visibility}:not(:checked)`
    ).length;
};

/**
 * Creates an edit controller state.
 *
 * @param {HTMLElement} workspace
 * @param {HTMLElement} controller
 * @param {HTMLElement|null} toolbar
 * @returns {Object}
 */
const createController = (
    workspace,
    controller,
    toolbar
) => {
    return {
        workspace,
        controller,
        toolbar,
        workspaceKey: getWorkspaceKey(workspace),
        active: false,
        dirty: false,
        saving: false,
        panelSnapshot: '',
        previouslyFocused: null,
    };
};

/**
 * Returns the localized toolbar status.
 *
 * @param {Object} state
 * @returns {string}
 */
const getStatusText = (state) => {
    if (!state.toolbar) {
        return '';
    }

    if (state.saving) {
        return state.toolbar.dataset.statusSaving || '';
    }

    if (state.dirty) {
        return state.toolbar.dataset.statusDirty || '';
    }

    return state.toolbar.dataset.statusClean || '';
};

/**
 * Updates the hidden-item counter.
 *
 * @param {Object} state
 */
const updateHiddenCount = (state) => {
    if (!state.toolbar) {
        return;
    }

    const count = countHiddenItems(
        state.controller
    );

    const countElement =
        state.toolbar.querySelector(
            SELECTORS.toolbarHiddenCount
        );

    const labelElement =
        state.toolbar.querySelector(
            SELECTORS.toolbarHiddenLabel
        );

    if (countElement) {
        countElement.textContent =
            String(count);
    }

    if (labelElement) {
        labelElement.textContent =
            count === 1
                ? state.toolbar.dataset.hiddenSingular || ''
                : state.toolbar.dataset.hiddenPlural || '';
    }
};

/**
 * Updates all toolbar controls.
 *
 * @param {Object} state
 */
const updateToolbar = (state) => {
    if (!state.toolbar) {
        return;
    }

    state.toolbar.hidden = !state.active;

    state.toolbar.dataset.workspaceToolbarState =
        state.saving
            ? 'saving'
            : state.dirty
                ? 'dirty'
                : 'clean';

    const status =
        state.toolbar.querySelector(
            SELECTORS.toolbarStatus
        );

    if (status) {
        status.textContent =
            getStatusText(state);
    }

    const save =
        state.toolbar.querySelector(
            SELECTORS.toolbarSave
        );

    const reset =
        state.toolbar.querySelector(
            SELECTORS.toolbarReset
        );

    const cancel =
        state.toolbar.querySelector(
            SELECTORS.toolbarCancel
        );

    if (save) {
        save.disabled =
            state.saving || !state.dirty;
    }

    if (reset) {
        reset.disabled = state.saving;
    }

    if (cancel) {
        cancel.disabled = state.saving;
    }

    state.toolbar.setAttribute(
        'aria-busy',
        state.saving ? 'true' : 'false'
    );

    updateHiddenCount(state);
};

/**
 * Shows or hides the editing chrome of Workspace items.
 *
 * @param {Object} state
 * @param {boolean} active
 */
const updateItemEditChrome = (
    state,
    active
) => {
    state.workspace.querySelectorAll(
        SELECTORS.item
    ).forEach((item) => {
        const chrome = item.querySelector(
            SELECTORS.itemEditChrome
        );

        if (!chrome) {
            return;
        }

        chrome.hidden = !active;

        item.classList.toggle(
            'is-workspace-item-editing',
            active
        );

        item.dataset.workspaceItemEditing =
            active ? '1' : '0';
    });
};

/**
 * Updates edit-mode classes and attributes.
 *
 * @param {Object} state
 * @param {boolean} active
 */
const applyModeState = (
    state,
    active
) => {
    updateItemEditChrome(
        state,
        active
    );

    state.workspace.classList.toggle(
        'is-workspace-editing',
        active
    );

    state.controller.classList.toggle(
        'is-workspace-editing',
        active
    );

    document.body.classList.toggle(
        'local-subscriptions-workspace-editing',
        active
    );

    state.workspace.dataset.workspaceEditing =
        active ? '1' : '0';

    state.controller.dataset.workspaceEditing =
        active ? '1' : '0';

    updateToolbar(state);
};

/**
 * Updates dirty state.
 *
 * @param {Object} state
 * @param {boolean} dirty
 */
const applyDirtyState = (
    state,
    dirty
) => {
    state.dirty = dirty;

    state.workspace.classList.toggle(
        'is-workspace-dirty',
        dirty
    );

    state.controller.classList.toggle(
        'is-workspace-dirty',
        dirty
    );

    state.workspace.dataset.workspaceDirty =
        dirty ? '1' : '0';

    state.controller.dataset.workspaceDirty =
        dirty ? '1' : '0';

    updateToolbar(state);

    dispatch(
        state.workspace,
        EVENTS.stateChanged,
        {
            workspace: state.workspaceKey,
            active: state.active,
            dirty: state.dirty,
            saving: state.saving,
        }
    );
};

/**
 * Enters edit mode.
 *
 * @param {Object} state
 */
const enter = (state) => {
    if (state.active) {
        return;
    }

    state.panelSnapshot =
        capturePanelSnapshot(
            state.controller
        );

    state.previouslyFocused =
        document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;

    state.active = true;
    state.saving = false;

    applyDirtyState(state, false);
    applyModeState(state, true);

    dispatch(
        state.workspace,
        EVENTS.entered,
        {
            workspace: state.workspaceKey,
        }
    );
};

/**
 * Leaves edit mode.
 *
 * @param {Object} state
 * @param {boolean} restore
 * @param {string} reason
 */
const exit = (
    state,
    restore = true,
    reason = 'cancel'
) => {
    if (!state.active) {
        return;
    }

    if (
        restore
        && !state.saving
    ) {
        restorePanelSnapshot(
            state.controller,
            state.panelSnapshot
        );
    }

    state.active = false;
    state.saving = false;
    state.panelSnapshot = '';

    applyDirtyState(state, false);
    applyModeState(state, false);

    dispatch(
        state.workspace,
        EVENTS.exited,
        {
            workspace: state.workspaceKey,
            reason,
            restored: restore,
        }
    );

    if (
        state.previouslyFocused
        && document.contains(
            state.previouslyFocused
        )
    ) {
        state.previouslyFocused.focus({
            preventScroll: true,
        });
    }

    state.previouslyFocused = null;
};

/**
 * Marks the draft as modified.
 *
 * @param {Object} state
 */
const markDirty = (state) => {
    if (
        !state.active
        || state.saving
    ) {
        return;
    }

    applyDirtyState(state, true);
};

/**
 * Marks the state as saving.
 *
 * @param {Object} state
 */
const markSaving = (state) => {
    if (!state.active) {
        return;
    }

    state.saving = true;
    updateToolbar(state);
};

/**
 * Restores controls after a save failure.
 *
 * @param {Object} state
 */
const markSaveFailed = (state) => {
    if (!state.active) {
        return;
    }

    state.saving = false;
    applyDirtyState(state, true);
};

/**
 * Resolves an event to a controller.
 *
 * @param {Event} event
 * @returns {Object|null}
 */
const findStateFromEvent = (event) => {
    const eventWorkspace =
        event.detail?.workspace || '';

    if (eventWorkspace) {
        return controllers.get(
            eventWorkspace
        ) || null;
    }

    const source =
        event.target instanceof HTMLElement
            ? event.target
            : null;

    const owner = source?.closest(
        '[data-workspace]'
    );

    const key = getWorkspaceKey(owner);

    return key
        ? controllers.get(key) || null
        : null;
};

/**
 * Registers global Workspace events.
 */
const registerDocumentEvents = () => {
    document.addEventListener(
        EVENTS.requestEnter,
        (event) => {
            const state =
                findStateFromEvent(event);

            if (state) {
                enter(state);
            }
        }
    );

    document.addEventListener(
        EVENTS.requestExit,
        (event) => {
            const state =
                findStateFromEvent(event);

            if (!state) {
                return;
            }

            exit(
                state,
                event.detail?.restore !== false,
                event.detail?.reason || 'cancel'
            );
        }
    );

    document.addEventListener(
        EVENTS.changed,
        (event) => {
            const state =
                findStateFromEvent(event);

            if (state) {
                markDirty(state);
            }
        }
    );

    document.addEventListener(
        EVENTS.saving,
        (event) => {
            const state =
                findStateFromEvent(event);

            if (state) {
                markSaving(state);
            }
        }
    );

    document.addEventListener(
        EVENTS.saveFailed,
        (event) => {
            const state =
                findStateFromEvent(event);

            if (state) {
                markSaveFailed(state);
            }
        }
    );
};

/**
 * Finds and initializes all Workspace controllers.
 */
const initializeControllers = () => {
    document.querySelectorAll(
        SELECTORS.controller
    ).forEach((controller) => {
        const workspaceKey =
            getWorkspaceKey(controller);

        if (
            !workspaceKey
            || controllers.has(workspaceKey)
        ) {
            return;
        }

        const workspace =
            document.querySelector(
                SELECTORS.workspace +
                '[data-workspace=\'' +
                CSS.escape(workspaceKey) +
                '\']'
            );

        if (!workspace) {
            return;
        }

        const toolbar =
            workspace.querySelector(
                SELECTORS.toolbar +
                '[data-workspace=\'' +
                CSS.escape(workspaceKey) +
                '\']'
            );

        const state = createController(
            workspace,
            controller,
            toolbar
        );

        controllers.set(
            workspaceKey,
            state
        );

        updateItemEditChrome(
            state,
            false
        );

        updateToolbar(state);
    });
};

let initialized = false;

/**
 * Initializes the Workspace edit-mode engine.
 */
export const init = () => {
    initializeControllers();

    if (initialized) {
        return;
    }

    registerDocumentEvents();
    initialized = true;
};