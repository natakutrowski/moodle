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
 * Role button.
 *
 * @module     block_gearup/role_button
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Delegate click to a zone.
 *
 * @param {String} rootSelector The root selector.
 * @param {String} nodeSelector The node on which the event must happen.
 * @param {Function} onClick The callback, receiving the node, and the root container.
 */
export function delegateClick(rootSelector, nodeSelector, onClick) {
    const nodes = document.querySelectorAll(rootSelector);

    const handleHit = (e) => {
        const node = e.target.closest('a,button,[role=button]');
        if (node && node.matches(nodeSelector)) {
            if (e.defaultPrevented) {
                return;
            }
            e.preventDefault();
            onClick(node, e.currentTarget);
        }
    };

    nodes.forEach(node => {
        node.addEventListener('click', handleHit);

        node.addEventListener('keydown', (e) => {
            if (e.key !== ' ' && e.key !== 'Enter') {
                return;
            }
            handleHit(e);
        });

        node.querySelectorAll(nodeSelector).forEach(node => {
            if (!node.getAttribute('role')) {
                node.setAttribute('role', 'button');
            }
            if (!node.hasAttribute('tabindex')) {
                node.setAttribute('tabindex', '0');
            }

            // When there is a direct onclick function, or a disabled attribute remove it.
            // We use onclick="return false" and disabled to prevent clicks until the event is registered.
            if (node.onclick) {
                node.onclick = undefined;
            }
            if (node.hasAttribute('disabled')) {
                node.removeAttribute('disabled');
            }
        });

    });
}

/**
 * Register a click.
 *
 * @param {String|Node} selectorOrNode The selector of one or more nodes, or a single node.
 * @param {Function} onClick The callback, receiving the node.
 */
export function registerClick(selectorOrNode, onClick) {
    const nodes = selectorOrNode instanceof Element ? [selectorOrNode] : document.querySelectorAll(selectorOrNode);

    nodes.forEach(node => {
        const handleHit = (e) => {
            if (e.defaultPrevented) {
                return;
            }
            e.preventDefault();
            onClick(node);
        };

        node.addEventListener('click', handleHit);
        node.addEventListener('keydown', (e) => {
            if (e.key !== ' ' && e.key !== 'Enter') {
                return;
            }
            handleHit(e);
        });

        if (!node.getAttribute('role')) {
            node.setAttribute('role', 'button');
        }
        if (!node.hasAttribute('tabindex')) {
            node.setAttribute('tabindex', '0');
        }

        // When there is a direct onclick function, or a disabled attribute remove it.
        // We use onclick="return false" and disabled to prevent clicks until the event is registered.
        if (node.onclick) {
            node.onclick = undefined;
        }
        if (node.hasAttribute('disabled')) {
            node.removeAttribute('disabled');
        }
    });
}
