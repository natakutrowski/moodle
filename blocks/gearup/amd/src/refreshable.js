// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * Refreshable.
 *
 * @module     block_gearup/refreshable
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import * as Templates from 'core/templates';
import Log from 'core/log';
import {extractNodeData, focusNodeByXPath, getXPathByTag, ws} from 'block_gearup/utils';

const FOCUS_REFRESH_DELAY = 8000;
const PAGE_LOADED_AT = Date.now();

/**
 * Register the observer.
 */
export function registerObserver() {
    registerOnWindowFocus();
}

let registeredOnWindowFocus = false;

/**
 * Regsiter auto-refresh on window focus.
 */
export function registerOnWindowFocus() {
    if (registeredOnWindowFocus) {
        return;
    }
    registeredOnWindowFocus = true;

    window.addEventListener('focus', () => {
        document.querySelectorAll('[data-gu-refreshable]').forEach((node) => {
            if (!isRefreshingNodeOnWindowFocusAllowed(node)) {
                return;
            }
            refreshTemplateContentsAtNode(node);
        });
    });
}

/**
 * Find the closest refreshable template.
 *
 * @param {HTMLElement} node
 * @returns {HTMLElement|null}
 */
function findClosestRefreshableTemplate(node) {
    return node.closest('[data-gu-refreshable][data-template][data-ws]') ?? null;
}

/**
 * Whether refreshing a node on window focus is allowed.
 *
 * @param {HTMLElement} node
 * @returns {Boolean}
 */
function isRefreshingNodeOnWindowFocusAllowed(node) {
    const lastRefresh = node.dataset.lastRefresh ? parseInt(node.dataset.lastRefresh, 10) : PAGE_LOADED_AT;
    if (lastRefresh > Date.now() - FOCUS_REFRESH_DELAY) {
        return false;
    }
    const modes = (node.dataset.guRefreshable ?? '').split(' ');
    return modes.includes('windowfocus');
}

/**
 * Pre content replacement process.
 *
 * @param {HTMLElement} node
 */
function preContentReplacementProcess(node) {
    // When tooltips are opened while we are replacing the nodes, they will linger around forever.
    node.querySelectorAll('[data-toggle="tooltip"],[data-bs-toggle="tooltip"]').forEach((tooltipNode) => {
        const jqNode = $(tooltipNode);
        if (!jqNode.tooltip) {
            return;
        }
        // Try to destroy any tooltip, with caution, due to different versions of tooltips.
        try {
            jqNode.tooltip("dispose");
        } catch (e) {
            try {
                jqNode.tooltip("destroy");
            } catch (e) {
                // Do nothing.
            }
        }
    });
}

/**
 * Post content replacement process.
 *
 * @param {HTMLElement} node
 */
function postContentReplacementProcess(node) {
    // We need to reactivate the tooltips.
    node.querySelectorAll('[data-toggle="tooltip"],[data-bs-toggle="tooltip"]').forEach((tooltipNode) => {
        const jqNode = $(tooltipNode);
        if (!jqNode.tooltip) {
            return;
        }
        // Try to re-enable the tooltip.
        try {
            jqNode.tooltip("enable");
        } catch (e) {
            // Do nothing.
        }
    });
}

/**
 * Refresh a template contents at a node.
 *
 * @param {HTMLElement} node
 */
async function refreshTemplateContentsAtNode(node) {
    const template = node.dataset.template;
    const wsName = node.dataset.ws;
    if (!template || !wsName) {
        return;
    }

    if (node.dataset.isRefreshing) {
        return;
    }

    if (!isValidWsMethod(wsName)) {
        return;
    }

    node.dataset.isRefreshing = true;
    try {
        const wsArgs = enhanceWsArgs(extractNodeData(node, 'wsArgs'));
        const data = await getDataFromWs(wsName, wsArgs);
        const {html, js} = await Templates.renderForPromise(template, data);
        preContentReplacementProcess(node);
        node = Templates.replaceNode(node, html, js)[0];
        postContentReplacementProcess(node);
    } catch (err) {
        // Error to the console to avoid spamming notofication exceptions.
        Log.error(err);
    }

    if (node) {
        node.dataset.lastRefresh = Date.now();
        delete node.dataset.isRefreshing;
    }
}

/**
 * Smart refresh contents.
 *
 * This attempts to resolve the items that need to be refreshed.
 *
 * @param {HTMLElement} node
 * @param {Boolean} [focusNode] Wether to focus the node after the refresh.
 * @returns {False|Promise} Whether anything was refreshed, or queued to be refreshed.
 */
export function smartRefreshFromNode(node, focusNode = false) {
    let promises = [];
    const initiatorXpath = getXPathByTag(node);

    const refreshableTemplate = findClosestRefreshableTemplate(node);
    if (refreshableTemplate) {
        promises.push(refreshTemplateContentsAtNode(refreshableTemplate));
    }

    if (!promises.length) {
        return false;
    }

    // Return a single promise without any arguments for now.
    return Promise.all(promises).then(() => {
        if (focusNode) {
            focusNodeByXPath(initiatorXpath);
        }
        return;
    });
}

/**
 * Enhance the WS arguments.
 *
 * @param {Object} wsArgs
 * @returns {Object}
 */
function enhanceWsArgs(wsArgs) {
    if (!wsArgs) {
        return {};
    }
    return Object.fromEntries(Object.entries(wsArgs).map(([key, value]) => {
        if (value === '__pageurl__') {
            value = window.location.href.replace(window?.M?.cfg?.wwwroot ?? '', '').split('#')[0];
        }
        return [key, value];
    }));
}

let WSPROMISES = {};

/**
 * Get data from WS.
 *
 * This prevent multiple simultaneous identical calls.
 *
 * @param {String} wsName The function name.
 * @param {Object} wsArgs The function arguments.
 * @returns {Promise}
 */
function getDataFromWs(wsName, wsArgs) {
    const refreshKey = `${wsName}:${JSON.stringify(sortObjectKeys(wsArgs))}`;
    if (refreshKey in WSPROMISES) {
        return WSPROMISES[refreshKey];
    }
    const p = ws(wsName, wsArgs);
    WSPROMISES[refreshKey] = p;

    p.then(() => {
        delete WSPROMISES[refreshKey];
        return;
    }).catch(() => {
        delete WSPROMISES[refreshKey];
    });

    return p;
}

/**
 * Validate that we refresh from this WS method.
 *
 * @param {String} name The WS method name.
 * @returns {Boolean}
 */
function isValidWsMethod(name) {
    if (!name.startsWith('block_gearup_')) {
        return false;
    }
    const fn = name.substring('block_gearup_'.length);
    if (!fn.startsWith('get_')) {
        return false;
    }
    return true;
}

/**
 * Sort an object keys.
 *
 * @param {Object} obj The object.
 * @returns {Object} A new sorted object.
 */
function sortObjectKeys(obj) {
    if (typeof obj !== 'object' || obj === null) {
        return obj;
    }

    if (Array.isArray(obj)) {
        return obj.map(sortObjectKeys);
    }

    const sortedObj = {};
    Object.keys(obj)
        .sort()
        .forEach(key => {
            sortedObj[key] = sortObjectKeys(obj[key]);
        });

    return sortedObj;
}
