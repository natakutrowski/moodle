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
 * Chat.
 *
 * @module     block_gearup/metadata
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

let loaderResolve;
let loaderReject;
let loadPromise = new Promise((resolve, reject) => {
    loaderResolve = resolve;
    loaderReject = reject;
    return null;
});

/**
 * Loader.
 */
function loader() {
    let selector = 'script[id^="gu-metadata-"][type="application/json"]';
    const node = document.querySelector(selector);
    if (!node) {
        return loaderReject();
    }

    const raw = node.textContent;
    node.remove();
    try {
        const data = JSON.parse(raw);
        loaderResolve(data);
    } catch (err) {
        loaderReject();
    }
    return null;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loader);
} else {
    loader();
}

/**
 * Get.
 */
export async function get() {
    return loadPromise;
}
