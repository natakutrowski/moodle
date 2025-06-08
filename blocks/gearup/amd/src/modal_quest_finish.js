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
 * Quest finish modal.
 *
 * @module     block_gearup/modal_quest_finish
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import Notification from 'core/notification';
import Ajax from 'core/ajax';
import log from 'core/log';
import Modal from 'core/modal';
import ModalRegistry from 'core/modal_registry';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import * as Compat from 'block_gearup/compat';
import * as Str from 'core/str';
import {delegateClick} from 'block_gearup/role_button';
import {markMissionSeen} from 'block_gearup/needs_attention_tracking';

// Register the modal.
// TODO Remove in favour of individual module, see MDL-79182.
ModalRegistry.register('block_gearup_quest_finish', Modal, 'block_gearup/quests/modal_finish');

// Trigger pre-loading.
Str.get_string('finish', 'block_gearup');
Templates.render('block_gearup/quests/modal_finish', []);

/**
 * Register a trigger.
 *
 * @param {Mixed} mixedSelector Trigger selector.
 */
function registerFromRoot(mixedSelector) {
    delegateClick(mixedSelector, '[data-action=finish]', (node, root) => {
        const nodeWithId = node.closest('[data-id]');
        if (!nodeWithId || !root.contains || !nodeWithId.dataset.id) {
            log.error('Mission data-id must be found in ancestor.');
        }
        open(nodeWithId.dataset.id, {
            onFinish: () => {
                window.location.reload();
                node.setAttribute('disabled', '');
            }
        });
    });
}

/**
 * Open the modal.
 *
 * @param {Number} missionInstId The mission instance ID.
 * @param {Object} options The options.
 */
async function open(missionInstId, options) {

    const modal = await ModalFactory.create(Compat.patchModalConfig({type: 'block_gearup_quest_finish'}));
    const finishBtn = modal.getFooter()[0].querySelector(modal.getActionSelector('finish'));
    modal.setLarge(true);

    // Dynamically load the content.
    const missionInst = Ajax.call([
        {
            methodname: 'block_gearup_get_mission_instance',
            args: {
                missioninstid: missionInstId,
            },
        },
    ])[0];

    // Set title and body promises.
    modal.setTitle(missionInst.then(data => data.mission.title));
    modal.setBody(missionInst.then(data => Templates.render('block_gearup/quests/modal_body', data)));

    // Tracks and prevents clicks before the content is rendered.
    let bodyRendered = false;
    finishBtn.setAttribute('disabled', '');

    // When the body renders.
    modal.getRoot().on(ModalEvents.bodyRendered, () => {
        bodyRendered = true;

        // Enable button on show.
        finishBtn.removeAttribute('disabled');
    });

    // When the modal is being closed.
    modal.getRoot().on(ModalEvents.hidden, async() => {

        // If closing the modal early, we do nothing!
        if (!bodyRendered) {
            return;
        }

        // Finish the mission as the modal is being closed.
        try {
            await Ajax.call([{methodname: 'block_gearup_finish_mission', args: {missioninstid: missionInstId}}])[0];
            if (options.onFinish) {
                options.onFinish();
            }
        } catch (e) {
            Notification.exception(e);
        }
    });

    // When the finish button is clicked.
    finishBtn.addEventListener('click', async e => {
        e.preventDefault();
        finishBtn.setAttribute('disabled', ''); // Prevent double clicks!
        modal.hide();
    });

    // Show the modal.
    modal.show();

    // Mark as seen.
    missionInst.then((data) => {
        if (!data.needsattention) {
            return;
        }
        setTimeout(() => markMissionSeen(missionInstId), 300);
        return;
    }).catch(() => null);
}

export {
    open,
    registerFromRoot
};