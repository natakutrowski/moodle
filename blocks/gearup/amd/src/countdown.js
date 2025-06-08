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
 * Countdown.
 *
 * @copyright  2023 Frédéric Massart
 * @module     block_gearup/countdown
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Str from 'core/str';

const DAYSECS = 86400;
const HOURSECS = 3600;
const MINSECS = 60;

/**
 * Human-friendly challenge time left.
 *
 * @param {Number} secsleft The seconds.
 * @returns {String}
 */
function timeLeft(secsleft) {
    if (secsleft <= 0) {
        return M.util.get_string('ended', 'block_gearup');
    } else if (secsleft < DAYSECS * 3) {
        const hours = Math.floor(secsleft / HOURSECS);
        const mins = Math.floor((secsleft - hours * HOURSECS) / MINSECS);
        const secs = secsleft - hours * HOURSECS - mins * MINSECS;
        return ('0' + hours).slice(-2) + ':' + ('0' + mins).slice(-2) + ':' + ('0' + secs).slice(-2);
    }
    const days = Math.floor(secsleft / DAYSECS);
    return M.util.get_string('numdays', 'core', days);
}

/**
 * Register the shortable observer.
 *
 * @param {String} rootSelector The selector.
 */
export function initFromRoot(rootSelector) {
    const stringLoadingPromise = Str.get_strings([{key: 'ended', component: 'block_gearup'}, {key: 'numdays', component: 'core'}]);
    const root = document.querySelector(rootSelector);
    if (!root) {
        return;
    }

    const nodes = root.querySelectorAll('[data-countdown]');
    const entries = [...nodes].map(node => {
        const deadline = parseInt(node.dataset.countdown, 10);
        if (!deadline) {
            return null;
        }
        return [node, deadline];
    }).filter(Boolean);

    if (!entries.length) {
        return;
    }

    stringLoadingPromise.then(() => {
        setInterval(function() {
            const nowSecs = Math.floor((new Date()).getTime() / 1000);
            entries.forEach(([node, deadline]) => {
                node.textContent = timeLeft(deadline - nowSecs);
            });
        }, 1000);
        return;
    }).catch(() => null);
}
