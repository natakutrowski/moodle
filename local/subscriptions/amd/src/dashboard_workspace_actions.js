/* eslint-env amd */
/**
 * Dashboard-specific Workspace item actions.
 *
 * @module local_subscriptions/dashboard_workspace_actions
 */

const EVENT_NAME =
    'local-subscriptions:workspace-item-action';

const WORKSPACE_KEY = 'dashboard';

let initialized = false;
let routes = {};

/**
 * Normalizes the route configuration received from PHP.
 *
 * @param {Object} configuration
 * @returns {Object}
 */
const normalizeRoutes = (configuration) => {
    if (
        !configuration
        || typeof configuration !== 'object'
        || Array.isArray(configuration)
    ) {
        return {};
    }

    const normalized = {};

    Object.entries(configuration).forEach(
        ([itemKey, url]) => {
            if (
                typeof itemKey !== 'string'
                || itemKey === ''
                || typeof url !== 'string'
                || url === ''
            ) {
                return;
            }

            normalized[itemKey] = url;
        }
    );

    return normalized;
};

/**
 * Handles one Dashboard Workspace contextual action.
 *
 * @param {CustomEvent} event
 */
const handleAction = (event) => {
    const detail = event.detail || {};

    if (
        detail.workspace !== WORKSPACE_KEY
        || detail.action !== 'open_details'
    ) {
        return;
    }

    const itemKey =
        typeof detail.item === 'string'
            ? detail.item
            : '';

    const url = routes[itemKey] || '';

    if (!url) {
        return;
    }

    window.location.assign(url);
};

/**
 * Initializes Dashboard-specific Workspace actions.
 *
 * @param {Object} configuration
 */
export const init = (configuration = {}) => {
    routes = normalizeRoutes(configuration.routes);

    if (initialized) {
        return;
    }

    document.addEventListener(
        EVENT_NAME,
        handleAction
    );

    initialized = true;
};