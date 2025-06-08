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
 * Utils for compatibility between Moodle versions.
 *
 * @module     block_gearup/compat
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import ModalRegistry from 'core/modal_registry';
import ModalForm from 'core_form/modalform';
import DynamicForm from 'core_form/dynamicform';

const IS_MODAL_TYPE_DEPRECATED = 'create' in Modal;

/**
 * Get form node.
 *
 * @param {ModalForm|DynamicForm} form The form.
 * @returns {Node}
 */
export function getFormNode(form) {
    try {
        return form.getFormNode();
    } catch (e) {
        if (form instanceof ModalForm) {
            return form.modal.getRoot().find('form')[0];
        } else if (form instanceof DynamicForm) {
            return form.container.querySelector('form');
        }
        return document.createElement('form');
    }
}

/**
 * Mark the form as submitted.
 *
 * @param {Node} node A DOM node.
 */
export function markFormSubmitted(node) {
    try {
        require('core_form/changechecker', function(ChangeChecker) {
            ChangeChecker.markFormSubmitted(node);
        });
    } catch (e) {
        if (typeof M.core_formchangechecker !== 'undefined') {
            M.core_formchangechecker.set_form_submitted();
        }
    }
}

/**
 * Patch a modal config to be compatible.
 *
 * This is mostly a mitigation between the deprecation of the 'type' (see MDL-78324),
 * and a bug in Moodle 4.3 that does not load the right template (MDL-81339).
 *
 * In time, we will have to convert away from using the type altogether in favour
 * of a separate modal module for each type of modal, see MDL-79182 for final deprecation.
 *
 * @param {Object} modalConfig The config.
 * @returns {Object}
 */
export function patchModalConfig(modalConfig) {
    if (IS_MODAL_TYPE_DEPRECATED) {
        // We hardcode the DEFAULT name to avoid importing the factory module.
        const type = modalConfig.type || 'DEFAULT';
        const {template} = ModalRegistry.get(type);
        return {
            ...modalConfig,
            template, // Force the template declaration due to MDL-81339 affecting custom templates with built-in modules.
        };
    }
    return modalConfig;
}
