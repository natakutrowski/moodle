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
 * Quest inprogress modal.
 *
 * @module     block_gearup/modal_quest_inprogress
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import Ajax from 'core/ajax';
import log from 'core/log';
import * as Str from 'core/str';
import * as Compat from 'block_gearup/compat';
import ModalFactory from 'core/modal_factory';
import {delegateClick} from 'block_gearup/role_button';
import {markMissionSeen} from 'block_gearup/needs_attention_tracking';

// Trigger pre-loading.
Str.get_string('ok', 'block_gearup');
Templates.render('block_gearup/quests/modal_body', []);

/**
 * Register a trigger.
 *
 * @param {Mixed} mixedSelector Trigger selector.
 */
function registerFromRoot(mixedSelector) {
    delegateClick(mixedSelector, '[data-element=trigger]', (node, root) => {
        const nodeWithId = node.closest('[data-id]');
        if (!nodeWithId || !root.contains || !nodeWithId.dataset.id) {
            log.error('Mission data-id must be found in ancestor.');
        }
        open(nodeWithId.dataset.id);
    });
}

/**
 * Open the modal.
 *
 * @param {Number} missionInstId The mission instance ID.
 */
async function open(missionInstId) {

    const modal = await ModalFactory.create(Compat.patchModalConfig({type: ModalFactory.types.CANCEL}));
    const cancelBtn = modal.getFooter().find(modal.getActionSelector('cancel'));
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

    // Change cancel button to "OK".
    Str.get_string('ok', 'block_gearup').then(s => {
        cancelBtn[0].textContent = s;
        return;
    }).catch(() => null);

    // Mark as seen.
    missionInst.then((data) => {
        if (!data.needsattention) {
            return;
        }
        setTimeout(() => markMissionSeen(missionInstId), 300);
        return;
    }).catch(() => null);

    // Show the modal.
    modal.show();
}

export {
    open,
    registerFromRoot
};