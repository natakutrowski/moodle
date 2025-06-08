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
 * Modal.
 *
 * @module     block_gearup/modal
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import Templates from 'core/templates';
import * as Compat from 'block_gearup/compat';
import {extractNodeData} from 'block_gearup/utils';

let simpleOpenModalActionObserverRegistered = false;
let simpleOpenModalActionObserverSelector = '[data-gu-action="open-modal"]';

/**
 * Open the modal.
 *
 * @param {Node} node The node.
 */
async function openSimpleModal(node) {
    const template = node.dataset.template;
    const templateContext = extractNodeData(node, 'templateContext');
    const modalArgs = extractNodeData(node, 'modal');

    let modalConfig = {
        body: Templates.render(template, templateContext),
    };
    if ('title' in modalArgs) {
        modalConfig.title = modalArgs.title;
    }
    if ('large' in modalArgs) {
        modalConfig.large = modalArgs.large;
    }

    const modal = await ModalFactory.create(Compat.patchModalConfig(modalConfig));
    modal.getRoot()[0].classList.add('block_gearup');
    modal.show();
}

/**
 * Register simple open modal action observer.
 */
export function registerSimpleOpenModalActionObserver() {
    if (simpleOpenModalActionObserverRegistered) {
        return;
    }
    simpleOpenModalActionObserverRegistered = true;
    document.body.addEventListener('click', (e) => {
        const node = e.target.closest(simpleOpenModalActionObserverSelector);
        if (!node) {
            return;
        }
        e.preventDefault();
        openSimpleModal(node);
    });
}
