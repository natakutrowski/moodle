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
 * Shortable.
 *
 * @module     block_gearup/shortable
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {delegateClick} from 'block_gearup/role_button';

let registered = false;

/**
 * Register the shortable observer.
 */
export function register() {
    if (registered) {
        return;
    }

    delegateClick('body', '.block_gearup [data-region=shortable] [data-action=expand]', (node) => {

        const root = node.closest('[data-region=shortable]');
        if (!root) {
            return;
        }

        const short = root.querySelector('[data-region=short]');
        const full = root.querySelector('[data-region=full]');

        short.classList.add('gu-hidden');
        full.style.display = '';
    });
}
