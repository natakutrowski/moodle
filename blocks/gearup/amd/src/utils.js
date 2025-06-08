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
 * Utils.
 *
 * @module     block_gearup/utils
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Ajax from 'core/ajax';

/**
 * Copy to clipboard.
 *
 * @param {String} str
 * @param {Node} [parentNode] The parent node for fallback logic.
 * @returns
 */
export async function copyToClipboard(str, parentNode) {
    if (navigator.clipboard) {
        try {
            await navigator.clipboard.writeText(str);
        } catch (e) {
            return;
        }
        return;
    }
    var el = document.createElement('textarea');
    el.value = str;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-99999px';
    const parent = parentNode || document.body;
    parent.appendChild(el);
    el.select();
    document.execCommand('copy');
    parent.removeChild(el);
}

/**
 * Extract data from dataset.
 *
 * This extracts data at a prefix, and converts in nested objects if needed.
 *
 * @param {Node} node The HTML node.
 * @param {String} prefix The data prefix.
 * @returns {Object}
 */
export function extractNodeData(node, prefix) {
    return Object.keys(node.dataset).filter(k => k.indexOf(prefix) === 0).reduce((carry, k) => {
        let value = node.dataset[k];
        if (value === 'true' || value === 'false') {
            value = value === 'true' ? true : false;
        }
        let key = k.charAt(prefix.length).toLocaleLowerCase() + k.substring(prefix.length + 1);

        if (key.indexOf('__') > -1) {
            return setAtDepth(carry, key.split('__').filter(k => k !== ''), value);
        }

        return {...carry, [key]: value};
    }, {});
}

/**
 * Focus a node by XPath.
 *
 * @param {String|null} xpath The XPath string.
 */
export function focusNodeByXPath(xpath) {
    if (!xpath) {
        return;
    }
    try {
        const node = document.evaluate(
            xpath,
            document,
            null,
            XPathResult.FIRST_ORDERED_NODE_TYPE,
            null
        ).singleNodeValue;
        node?.focus();
    } catch (err) {
        // Fail safe.
        return;
    }
}

/**
 * Get the XPath of an element.
 *
 * @param {HTMLElement} element The element.
 * @returns {String|null}
 */
export function getXPathByTag(element) {
    if (element === document.body) {
        return '/html/body';
    }
    let idx = 0;
    const siblings = element.parentNode.childNodes;
    for (let i = 0; i < siblings.length; i++) {
        const sibling = siblings[i];
        if (sibling === element) {
            const tagName = element.tagName.toLowerCase();
            return getXPathByTag(element.parentNode) + '/' + tagName + '[' + (idx + 1) + ']';
        }
        if (sibling.nodeType === Node.ELEMENT_NODE && sibling.tagName === element.tagName) {
            idx++;
        }
    }

    // This should not happen, but if it does...
    return null;
}

/**
 * Set a value at a specific depth in an object.
 *
 * @param {Object} obj
 * @param {String[]} keys
 * @param {Any} value
 * @returns {Object}
 */
function setAtDepth(obj, keys, value) {
    let currentObj = obj;

    for (let i = 0; i < keys.length - 1; i++) {
        const key = keys[i];
        currentObj[key] = typeof currentObj[key] === 'undefined' ? {} : currentObj[key];
        currentObj = currentObj[key];
    }

    const lastKey = keys[keys.length - 1];
    currentObj[lastKey] = value;

    return obj;
}

/**
 * Ajax call shorthand.
 *
 * @param {String} method The method.
 * @param {Object} args  The arguments.
 * @returns {Promise}
 */
export function ws(method, args) {
    return Ajax.call([{methodname: method, args: args}])[0];
}
