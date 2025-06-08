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
 * Quest accept modal.
 *
 * @module     block_gearup/modal_quest_accept
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import Notification from 'core/notification';
import Ajax from 'core/ajax';
import * as Str from 'core/str';
import log from 'core/log';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import ModalRegistry from 'core/modal_registry';
import ModalFactory from 'core/modal_factory';
import * as Compat from 'block_gearup/compat';
import {delegateClick} from 'block_gearup/role_button';
import {markMissionSeen} from 'block_gearup/needs_attention_tracking';

// Register the modal.
// TODO Remove in favour of individual module, see MDL-79182.
ModalRegistry.register('block_gearup_quest_accept', Modal, 'block_gearup/quests/modal_accept');

// Trigger pre-loading.
Str.get_strings([{key: 'youvegotaquest', component: 'block_gearup'}, {key: 'ok', component: 'block_gearup'}]);
Templates.render('block_gearup/quests/modal_accept', []);
Templates.render('block_gearup/quests/modal_body', []);

/**
 * Register a trigger.
 *
 * @param {Mixed} mixedSelector Trigger selector.
 */
function registerFromRoot(mixedSelector) {
    delegateClick(mixedSelector, '[data-action=accept]', (node, root) => {
        const nodeWithId = node.closest('[data-id]');
        if (!nodeWithId || !root.contains || !nodeWithId.dataset.id) {
            log.error('Mission data-id must be found in ancestor.');
        }
        open(nodeWithId.dataset.id, {
            onAccept: () => {
                window.location.reload();
                root.remove();
            }
        });
    });
}

/**
 * Open the modal.
 *
 * @param {Number} missionInstId The mission instance ID.
 * @param {Object} [options] The options.
 */
async function open(missionInstId, options) {
    options = options || {};

    const modal = await ModalFactory.create(
        Compat.patchModalConfig({
            type: 'block_gearup_quest_accept',
            templateContext: {loading: true},
        })
    );
    modal.getRoot().on(ModalEvents.outsideClick, (e) => e.preventDefault());
    modal.setLarge(true);

    // Disable the accept button.
    const footer = modal.getFooter()[0];
    const acceptBtn = footer.querySelector(modal.getActionSelector('accept'));
    const cancelBtn = footer.querySelector(modal.getActionSelector('cancel'));
    acceptBtn.setAttribute('disabled', '');

    // Dynamically load the content.
    const missionInst = Ajax.call([
        {
            methodname: 'block_gearup_get_mission_instance',
            args: {
                missioninstid: missionInstId,
            },
        },
    ])[0];

    modal.setBody(missionInst.then(data => Templates.render('block_gearup/quests/modal_body', data)));
    modal.getRoot().on(ModalEvents.bodyRendered, () => {
        acceptBtn.removeAttribute('disabled');
    });

    // Show the modal.
    modal.show();
    markMissionSeenFromPromise(missionInst);

    const acceptCallback = async(e) => {
        e.preventDefault();
        acceptBtn.setAttribute('disabled', '');

        try {
            await Ajax.call([
                {
                    methodname: 'block_gearup_accept_mission',
                    args: {
                        missioninstid: missionInstId,
                    },
                },
            ])[0];

            const missionInst = await Ajax.call([
                {
                    methodname: 'block_gearup_get_mission_instance',
                    args: {
                        missioninstid: missionInstId,
                    },
                },
            ])[0];

            modal.setBody(Templates.render('block_gearup/quests/modal_body', {
                ...missionInst,
                dialogueupdated: true,
            }));
            markMissionSeenFromPromise(missionInst);

            // Swap buttons.
            cancelBtn.style.display = 'none';
            Str.get_string('ok', 'block_gearup').then(s => {
                acceptBtn.textContent = s;
                return;
            }).catch(() => null);
            acceptBtn.removeEventListener('click', acceptCallback);
            acceptBtn.addEventListener('click', () => {
                modal.hide();
                if (options.onAccept) {
                    options.onAccept();
                }
            });


        } catch (e) {
            Notification.exception(e);
        }

        acceptBtn.removeAttribute('disabled');
    };

    // Register the click on buttons.
    acceptBtn.addEventListener('click', acceptCallback);
    cancelBtn.addEventListener('click', function(e) {
        e.preventDefault();
        modal.hide();
    });

}

/**
 * Mark a mission seen from a promise.
 *
 * @param {Promise} missionInstPromise
 * @returns
 */
async function markMissionSeenFromPromise(missionInstPromise) {
    try {
        const missionInst = await missionInstPromise;
        if (!missionInst.needsattention) {
            return;
        }
        setTimeout(() => markMissionSeen(missionInst.id), 300);
    } catch (e) {
        return;
    }
}

export {
    open,
    registerFromRoot
};