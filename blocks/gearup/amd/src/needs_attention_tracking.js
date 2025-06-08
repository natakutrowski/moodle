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
 * Needs attention tracking.
 *
 * @module     block_gearup/needs_attention_tracking
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

const listeners = {};

export const hideAttentionMarkerAnywhere = (missionInstId) => {
    document.querySelectorAll(`[data-gu-type="missioninst"][data-id="${missionInstId}"] [data-attention]`).forEach((n) => {
        if (n.dataset.attention === 'hide') {
            n.style.display = 'none';
        }
    });
};

export const markMissionSeen = async(missionInstId) => {
    try {
        await Ajax.call([{
            methodname: 'block_gearup_mark_mission_seen',
            args: {missioninstid: missionInstId}
        }]);
        hideAttentionMarkerAnywhere(missionInstId);
        const fns = listeners[missionInstId] || [];
        fns.forEach(fn => fn(missionInstId));
    } catch (e) {
        // Ignore.
    }
};

export const observeMissionMarkedSeen = (missionInstId, fn) => {
    listeners[missionInstId] = (listeners[missionInstId] || []).concat([fn]);
};
