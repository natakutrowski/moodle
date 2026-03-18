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
 * User selector transport.
 *
 * This is heavily inspired by {@link core_user/form_user_selector}.
 *
 * @copyright  2021 Frédéric Massart
 * @module     block_gearup/form_user_selector
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';

/**
 * Transport function.
 *
 * @param {String} selector The selector of the auto complete element.
 * @param {String} query The query string.
 * @param {Function} callback A callback function receiving an array of results.
 * @param {Function} failure A function to call in case of failure, receiving the error message.
 */
export async function transport(selector, query, callback, failure) {

    const el = document.querySelector(selector);
    if (!el) {
        failure(new Error('Could not identify user selector element'));
        return;
    }

    const request = {
        methodname: 'block_gearup_search_users',
        args: {
            contextid: el.dataset.contextid,
            query: query
        }
    };

    try {
        const users = await Ajax.call([request])[0];

        // Pre-render the template.
        await Templates.render('block_gearup/form_user_selector_item', {});

        // Render all the labels.
        const labelPromises = users.map(user => {
            return Templates.render('block_gearup/form_user_selector_item', user);
        });
        const labels = await Promise.all(labelPromises);

        // Set the label value on each user.
        users.forEach((user, index) => {
            user.label = labels[index];
        });

        callback(users);

    } catch (e) {
        failure(e);
    }
}

/**
 * Process the results for auto complete elements.
 *
 * @param {String} selector The selector of the auto complete element.
 * @param {Array} results An array or results returned by {@see transport()}.
 * @return {Array} New array of the selector options.
 */
export function processResults(selector, results) {
    if (!Array.isArray(results)) {
        return results;
    }
    return results.map(result => ({value: result.id, label: result.label}));
}
