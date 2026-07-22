/* eslint-env amd */
/**
 * Compatibility wrapper for the generic Workspace personalization UI.
 *
 * @module local_subscriptions/dashboard_personalization
 */

import {
    init as initWorkspacePersonalization,
} from './workspace_personalization';

/**
 * Initializes the generic Workspace personalization engine.
 */
export const init = () => {
    initWorkspacePersonalization();
};