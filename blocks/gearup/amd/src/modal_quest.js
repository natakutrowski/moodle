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
 * @module     block_gearup/modal_quest
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Chat from 'block_gearup/chat';
import * as Compat from 'block_gearup/compat';
import {markMissionSeen} from 'block_gearup/needs_attention_tracking';
import {delegateClick} from 'block_gearup/role_button';
import {getPreference, setPreference, ws} from 'block_gearup/utils';
import log from 'core/log';
import ModalEvents from 'core/modal_events';
import ModalCancel from 'core/modal_cancel';
import Notification from 'core/notification';
import {get_string as getString, get_strings as getStrings} from 'core/str';
import Pending from 'core/pending';
import Templates from 'core/templates';

const MODE_ACCEPT = 'accept';
const MODE_VIEW = 'view';
const MODE_FINISH = 'finish';
const MODES = [MODE_ACCEPT, MODE_VIEW, MODE_FINISH];

const STRINGS = [
    {key: 'ok', component: 'block_gearup'},
    {key: 'objectives', component: 'block_gearup'},
    {key: 'rewards', component: 'block_gearup'},
    {key: 'completed', component: 'block_gearup'},
    {key: 'incomplete', component: 'block_gearup'},
    {key: 'mute', component: 'block_gearup'},
    {key: 'unmute', component: 'block_gearup'},
    {key: 'thankyou', component: 'block_gearup'},
];

/**
 * Prepare the modal.
 * @param {Object} modal
 * @param {Promise} renderPromise
 * @param {Function} onRender
 */
function prepareModal(modal, renderPromise, onRender) {
    modal.setLarge(true);
    modal.getRoot().on(ModalEvents.outsideClick, (e) => e.preventDefault());
    modal.setBody(renderPromise.then(() => '<div data-gu-region="chat"></div>'));
    modal.getRoot().on(ModalEvents.bodyRendered, onRender);
}

/**
 * Create the app.
 *
 * @param {Object} modal
 * @param {Object} missionChat
 * @param {Boolean} muted
 */
async function createApp(modal, missionChat, muted) {
    let app;
    try {
        app = await Chat.init(modal.getBody().find('[data-gu-region="chat"]')[0], {
            conversation: convertMissionChatForApp(missionChat),
            getString: async(key, args) => {
                const component = STRINGS.find((s) => s.key === key)?.component;
                if (!component) {
                    return Promise.reject();
                }
                return getString(key, component, args);
            },
            settings: {
                scrollable: false,
                muted
            }
        });
    } catch (e) {
        log.error('Failed to initialise chat', e);
        Notification.exception(e);
    }
    return app;
}

/**
 * Prepare the app.
 *
 * @param {Object} app
 * @param {Object} modal
 * @param {Promise} pendingPromise
 */
function prepareApp(app, modal, pendingPromise) {
    // Observe scroll position of the body.
    const modalBody = modal.getBody()[0];
    modalBody.style.position = 'relative';
    let isScrollAtBottom = true;
    modalBody.addEventListener('scroll', () => {
        isScrollAtBottom = modalBody.scrollTop >= modalBody.scrollHeight - modalBody.clientHeight;
    });
    const adjustScrollPosition = () => {
        if (!isScrollAtBottom) {
            return;
        }
        setTimeout(() => {
            modalBody.scrollTop = modalBody.scrollHeight;
        }, 0);
    };

    app.on('queue.empty', () => {
        adjustScrollPosition();
        if (pendingPromise) {
            pendingPromise.resolve();
            pendingPromise = null;
        }
    });

    app.on('audio.muted', async({muted}) => {
        await setPreference('block_gearup_muted', Boolean(muted));
    });

    app.on('history.displayed', () => {
        adjustScrollPosition();
    });

    app.on('message.displaying', () => {
        adjustScrollPosition();
    });

    app.on('message.finished', () => {
        adjustScrollPosition();
    });

    let _destroyed = false;
    modal.getRoot().on(ModalEvents.hidden, () => {
        if (_destroyed) {
            return;
        }
        _destroyed = true;
        app.send('app.destroy');
    });
}

/**
 * Open.
 *
 * @param {Number} missionInstId
 * @param {Object} [options]
 */
export async function open(missionInstId, options = {}) {
    options = options ?? {};
    let mode = options.mode;

    const missionInstPromise = ws('block_gearup_get_mission_instance', {missioninstid: missionInstId});
    if (!MODES.includes(mode)) {
        mode = await missionInstPromise.then((inst) => {
            if (!inst.hasstarted) {
                return MODE_ACCEPT;
            } else if (inst.iscompleted) {
                return MODE_FINISH;
            }
            return MODE_VIEW;
        });
    }

    let config = {removeOnClose: true};
    if (mode === MODE_ACCEPT) {
        config.template = 'block_gearup/quests/modal_accept';
    } else if (mode === MODE_VIEW) {
        config.template = 'block_gearup/quests/modal_view';
    } else if (mode === MODE_FINISH) {
        config.template = 'block_gearup/quests/modal_finish';
    }

    // We want all of this to resolve before we render.
    let pendingPromise = new Pending('block_gearup/modal_quest:render');
    const renderPromise = Promise.all([missionInstPromise, getPreference('block_gearup_muted', false)])
        .then(([missionInst, muted]) => ({missionInst, muted}));

    const modal = await Compat.createModal(config);
    modal.registerCloseOnCancel();
    prepareModal(modal, renderPromise, async() => {

        const {missionInst, muted} = await renderPromise;
        const confirmBtn = modal.getFooter().find(modal.getActionSelector('confirm'))[0];

        // When the title is empty, we set it to the mission title.
        if (modal.getTitle()[0].textContent.trim() === '') {
            modal.setTitle(missionInst.mission.title);
        }

        const app = await createApp(modal, missionInst.chat, muted);
        if (!app) {
            return;
        }
        prepareApp(app, modal, pendingPromise);

        app.on('assets.loaded', () => {
            markMissionSeenFromPromise(missionInstPromise);
        });

        app.on('queue.empty', () => {
            if (confirmBtn) {
                confirmBtn.removeAttribute('disabled');
            }
        });

        // Observe the confirm button.
        if (confirmBtn) {
            confirmBtn.addEventListener('click', async(e) => {
                e.preventDefault();
                confirmBtn.setAttribute('disabled', '');
                try {
                    await handleConfirm(modal, missionInst, app);
                } catch (e) {
                    Notification.exception(e);
                }
                confirmBtn.removeAttribute('disabled');
            });
        }

        // Observe the cancel button, or equivalent.
        modal.getRoot().on(ModalEvents.hidden, async() => {
            try {
                await handleHidden(modal, missionInst, app);
            } catch (e) {
                Notification.exception(e);
            }
        });

        app.start();
    });
    modal.show();
}

/**
 * Open.
 *
 * @param {Number} missionId
 * @param {String} mode
 */
export async function openPreview(missionId, mode) {
    if (!MODES.includes(mode)) {
        mode = MODE_VIEW;
    }

    let state = 'assigned';
    if (mode === MODE_VIEW) {
        state = 'started';
    } else if (mode === MODE_FINISH) {
        state = 'completed';
    }

    const pendingPromise = new Pending('block_gearup/modal_quest:render');
    const chatPromise = ws('block_gearup_get_mission_chat', {missionid: missionId, state, needsattention: true});
    const renderPromise = Promise.all([chatPromise, getPreference('block_gearup_muted', false)])
        .then(([missionChat, muted]) => ({missionChat, muted}));

    const modal = await Compat.createModal({removeOnClose: true, type: 'CANCEL'}, ModalCancel);
    modal.setTitle(getString('preview', 'core'));
    prepareModal(modal, renderPromise, async() => {
        const {missionChat, muted} = await renderPromise;
        modal.setButtonText('cancel', getString('closebuttontitle', 'moodle'));

        const app = await createApp(modal, missionChat, muted);
        if (!app) {
            return;
        }
        prepareApp(app, modal, pendingPromise);
        app.start();
    });
    modal.show();
}


/**
 * Confirm.
 *
 * @param {Object} modal
 * @param {Object} missionInst
 * @param {Object} app
 */
async function handleConfirm(modal, missionInst, app) {
    if (!missionInst.hasstarted) {
        const pending = new Pending('block_gearup/modal_quest:accept');
        await ws('block_gearup_accept_mission', {missioninstid: missionInst.id});

        const refreshedMissionInst = ws('block_gearup_get_mission_instance', {missioninstid: missionInst.id});
        markMissionSeenFromPromise(refreshedMissionInst);

        const confirmBtn = modal.getFooter().find(modal.getActionSelector('confirm'))[0];
        if (confirmBtn) {
            confirmBtn.style.display = 'none';
        }
        const cancelBtn = modal.getFooter().find(modal.getActionSelector('cancel'))[0];
        if (cancelBtn) {
            cancelBtn.setAttribute('disabled', '');
        }

        if ((await refreshedMissionInst).iscompleted) {
            getString('thankyou', 'block_gearup').then((t) => modal.setButtonText('cancel', t)).catch(() => null);
        } else {
            getString('ok', 'block_gearup').then((t) => modal.setButtonText('cancel', t)).catch(() => null);
        }

        app.on('queue.empty', () => {
            if (cancelBtn) {
                cancelBtn.removeAttribute('disabled');
            }
            pending.resolve();
        });
        app.send('queue.push', convertMissionChatForApp((await refreshedMissionInst).chat).items);
        modal.getRoot().on(ModalEvents.hidden, async() => {
            if ((await refreshedMissionInst).iscompleted) {
                try {
                    await ws('block_gearup_finish_mission', {missioninstid: missionInst.id});
                } catch (e) {
                    // Silently ignore errors.
                }
            }
            removeMissionFromDocument(missionInst.id);
            window.location.reload();
        });

        return;
    }

    // All other actions trigger a hide.
    modal.hide();
}

/**
 * Handle hidden.
 *
 * @param {Object} modal
 * @param {Object} missionInst
 * @param {Object} app
 */
async function handleHidden(modal, missionInst, app) {
    if (missionInst.iscompleted) {
        // Finish the mission when we dismiss the modal, however how.
        try {
            await ws('block_gearup_finish_mission', {missioninstid: missionInst.id});
        } catch (e) {
            // Silently ignore errors.
        }
        removeMissionFromDocument(missionInst.id);
        window.location.reload();
        return;
    }
}

/**
 * Remove mission from the document.
 *
 * @param {Number} missionInstId
 */
async function removeMissionFromDocument(missionInstId) {
    document.querySelectorAll(`[data-gu-type="missioninst"][data-id="${missionInstId}"]`).forEach((n) => {
        n.style.display = 'none';
    });
}

/**
 * Convert chat.
 *
 * @param {Object} chat
 * @returns {Object}
 */
function convertMissionChatForApp(chat) {
    const convo = {...chat};
    convo.items = convo.items.map((item) => {
        return {
            ...item,
            [item.type]: undefined,
            ...item[item.type],
        };
    });
    return convo;
}

/**
 * Register a trigger.
 *
 * @param {Mixed} mixedSelector Trigger selector.
 */
export function registerFromRoot(mixedSelector) {
    preloadAssets();
    delegateClick(mixedSelector, '[data-gu-action="open-quest"]', (node, root) => {
        const nodeWithId = node.closest('[data-id]');
        if (!nodeWithId || !root.contains || !nodeWithId.dataset.id) {
            log.error('Mission data-id must be found in ancestor.');
        }
        open(nodeWithId.dataset.id);
    });
}

/**
 * Register a preview trigger.
 *
 * @param {Mixed} mixedSelector Trigger selector.
 */
export function registerPreviewFromRoot(mixedSelector) {
    preloadAssets();
    delegateClick(mixedSelector, '[data-gu-action="open-quest-preview"]', (node, root) => {
        const nodeWithId = node.closest('[data-id]');
        if (!nodeWithId || !root.contains || !nodeWithId.dataset.id) {
            log.error('Mission data-id must be found in ancestor.');
        }
        openPreview(nodeWithId.dataset.id, node.dataset.mode);
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

/**
 * Preload assets.
 */
function preloadAssets() {
    getStrings(STRINGS);
    Templates.prefetchTemplates([
        'block_gearup/quests/modal_accept',
        'block_gearup/quests/modal_finish',
        'block_gearup/quests/modal_view'
    ]);
}
