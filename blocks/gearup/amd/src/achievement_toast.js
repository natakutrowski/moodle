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
 * Achievement toast.
 *
 * @module     block_gearup/achievement_toast
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable promise/no-nesting */

import Templates from 'core/templates';
import Notification from 'core/notification';
import {registerClick} from 'block_gearup/role_button';
import {ws} from 'block_gearup/utils';
import * as Str from 'core/str';
import * as Compat from 'block_gearup/compat';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import ModalRegistry from 'core/modal_registry';
import Modal from 'core/modal';
import {throwCelebrationConfettis, throwRandomConfettis} from 'block_gearup/confetti-lazy';

const seenDuration = 2000;
const hideDuration = 1000;
const hideAfter = 5000;

const rootContainer = document.createElement('div');
rootContainer.classList.add('block_gearup');

let working = false;
let pause = false;
let queuedMissionInstIds = [];
let missionInstIdsDealtWith = [];
let registeredOnWindowFocus = false;
let windowHasFocus = true;

document.body.appendChild(rootContainer);

// Modal registration.
// TODO Remove in favour of individual module, see MDL-79182.
ModalRegistry.register('block_gearup_achievement_celebration', Modal, 'block_gearup/achievements/modal_celebration');

// Trigger pre-loading.
Str.get_strings([
    {key: 'awesomeexcl', component: 'block_gearup'},
    {key: 'achievementunlockedexcl', component: 'block_gearup'},
    {key: 'moreconfettis', component: 'block_gearup'}
]);
Templates.render('block_gearup/achievements/modal_celebration', []);
Templates.render('block_gearup/achievements/modal_body', []);


/**
 * Open the modal.
 *
 * @param {Object} inst The instance.
 */
async function openModal(inst) {
    pause = true;

    const body = Templates.render('block_gearup/achievements/modal_body', inst);
    const modal = await ModalFactory.create(Compat.patchModalConfig({
        type: 'block_gearup_achievement_celebration',
        body: body,
        title: Str.get_string('achievementunlockedexcl', 'block_gearup')
    }));
    modal.setRemoveOnClose(true);
    modal.registerCloseOnCancel();

    const footer = modal.getFooter()[0];
    const cancelBtn = footer.querySelector(modal.getActionSelector('cancel'));
    const confettiBtn = footer.querySelector(modal.getActionSelector('confetti'));

    registerClick(confettiBtn, () => {
        throwRandomConfettis();
    });

    modal.getRoot().on(ModalEvents.bodyRendered, () => {
        setTimeout(() => {
            throwCelebrationConfettis();
        }, 300);
        setTimeout(() => {
            confettiBtn.classList.remove('gu-invisible');
            confettiBtn.classList.remove('gu-opacity-0');
        }, 1000);
    });

    modal.getRoot().on(ModalEvents.hidden, () => {
        pause = false;
    });

    Str.get_string('awesomeexcl', 'block_gearup').then(s => {
        cancelBtn.textContent = s;
        return;
    }).catch(() => null);

    modal.show();
}

/**
 * Show the achievement.
 *
 * @param {Object} inst The instance.
 * @returns {Promise} The promise.
 */
function showInstance(inst) {
    return Templates.render('block_gearup/achievements/toast', inst).then((html, js) => {

        // Show the toast.
        Templates.replaceNodeContents(rootContainer, html, js);
        const node = rootContainer.childNodes[0];
        node.classList.add('gu-toast-show');

        // Register click.
        registerClick(node, () => {
            openModal(inst, {});
        });

        // Mark as seen.
        setTimeout(async() => {
            try {
                await ws('block_gearup_mark_mission_seen', {missioninstid: inst.id});
            } catch (err) {
                // Nothing.
            }
        }, seenDuration);

        // Hide after...
        let hidePromise = registerHide(node);

        // Notify when this is finished.

        return hidePromise.then(() => {
            node.remove();
            return;
        }).catch(() => {
            return;
        });

    }).fail(Notification.exception);
}

/**
 * Register to be hidden.
 *
 * @param {Noed} node The node.
 * @returns {Promise}
 */
function registerHide(node) {
    const p = new Promise((resolve) => {

        const registeredOn = Date.now();
        let hideActivated = false;
        let hideIn = hideAfter;
        let hideTimeout;

        // When we hover the widget, we cancel the hide effect.
        node.addEventListener('mouseover', () => {
            if (hideActivated) {
                return;
            }
            clearTimeout(hideTimeout);
            hideIn = Math.max(1000, (registeredOn + hideAfter) - Date.now());
        });

        // When we leave the widget, we resume the hide effect timeout.
        node.addEventListener('mouseout', () => {
            if (hideActivated) {
                return;
            }
            prepareHide();
        });

        const prepareHide = () => {
            hideTimeout = setTimeout(() => {
                if (!hideActivated && pause) {
                    hideIn = 1500;
                    prepareHide();
                    return;
                }

                hideActivated = true;
                node.classList.add('gu-toast-hide');
                setTimeout(resolve, hideDuration);
            }, hideIn);
        };

        prepareHide();
    });

    return p;
}

/**
 * Do the thing.
 *
 * @param {Number} delay The delay.
 */
function doTheWork(delay) {
    setTimeout(async() => {
        if (!working) {
            return;
        } else if (!windowHasFocus) {
            stopWorker();
            return;
        }

        const id = queuedMissionInstIds.splice(0, 1)[0];
        if (!id) {
            stopWorker();
            return;
        }

        // In order to prevent some kind of odd race conditions.
        if (missionInstIdsDealtWith.includes(id)) {
            doTheWork();
            return;
        }
        missionInstIdsDealtWith.push(id);

        // Fetch the instance.
        let inst;
        try {
            inst = await ws('block_gearup_get_mission_instance', {missioninstid: id});
        } catch (err) {
            doTheWork();
            return;
        }

        // Another dealing with race conditions I guess.
        if (!inst || !inst.needsattention) {
            doTheWork();
            return;
        }

        // Ok, now show the thing, and once it's hidden, carry on.
        try {
            await showInstance(inst);
            doTheWork(1000);
        } catch (err) {
            stopWorker();
        }
    }, delay);
}

/**
 * Notify (start) the worker.
 */
function notifyWorker() {
    if (!working) {
        working = true;
        registerOnWindowFocus();
        doTheWork(1000);
    }
}

/**
 * Stop the worker.
 */
function stopWorker() {
    working = false;
}

/**
 * Regsiter auto-refresh on window focus.
 */
function registerOnWindowFocus() {
    if (registeredOnWindowFocus) {
        return;
    }
    registeredOnWindowFocus = true;
    window.addEventListener('focus', () => {
        windowHasFocus = true;
        if (queuedMissionInstIds.length) {
            notifyWorker();
        }
    });
    window.addEventListener('blur', () => {
        windowHasFocus = false;
    });
}

/**
 * Init.
 *
 * @param {Array} achievementInstances Contains objects with id, mission.title.
 * @deprecated Since Quest 1.6, use queueMissionInstanceIds instead.
 */
const init = (achievementInstances) => {
    queueMissionInstanceIds(achievementInstances.map(inst => inst.id));
    notifyWorker();
};

/**
 * Init with JSON node.
 *
 * @param {String} selector The JSON node selector.
 * @deprecated Since Quest 1.6, use queueMissionInstanceIds instead.
 */
const initWithJson = (selector) => {
    try {
        const node = document.querySelector(selector);
        const data = node ? JSON.parse(node.textContent) : null;
        if (Array.isArray(data)) {
            init(data);
        }
    } catch (err) {
        // Nothing.
    }
};

/**
 * Queue mission instance IDs.
 *
 * @param {Number[]} missionInstIds The mission instance IDs.
 */
const queueMissionInstanceIds = (missionInstIds) => {
    for (const id of missionInstIds) {
        if (!queuedMissionInstIds.includes(id)) {
            queuedMissionInstIds.push(id);
        }
    }
    notifyWorker();
};

export {
    init,
    initWithJson,
    queueMissionInstanceIds
};

