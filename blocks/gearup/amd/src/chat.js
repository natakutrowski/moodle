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
 * Chat.
 *
 * @module     block_gearup/chat
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get} from 'block_gearup/metadata';
import cfg from 'core/config';

let loadPromise;

/**
 * Load the library.
 */
export async function load() {
    if (!loadPromise) {
        loadPromise = new Promise((resolve, reject) => {
            get().then((metadata) => {
                if (!metadata || !metadata.chatlib || !metadata.chatlib.url) {
                    throw new Error('Chat library metadata is missing.');
                }
                return metadata.chatlib;
            }).then((chatlib) => {
                const script = document.createElement('script');
                script.src = chatlib.url;
                if (chatlib.integrity || !cfg?.developerdebug) {
                    script.integrity = chatlib.integrity ?? 'sha256-invalid';
                }
                script.crossOrigin = 'anonymous';
                script.referrerPolicy = 'origin';
                script.onload = () => {
                    if (window.LevelUpChat) {
                        resolve();
                    } else {
                        reject(new Error('Failed to load Level Up Chat.'));
                    }
                };
                script.onerror = (e) => reject(new Error(`Failed to load Level Up Chat: ${e.message ?? 'Unspecified error'}`));
                document.head.appendChild(script);
                return;
            }).catch(reject);
        });
    }
    return loadPromise;
}

/**
 * Init the chat.
 *
 * @param {HTMLElement} node
 * @param {Object} props
 */
export async function init(node, props) {
    await load();
    if (typeof window.LevelUpChat !== 'object' || !window.LevelUpChat.init) {
        throw new Error('LevelUpChat is not available.');
    }
    return window.LevelUpChat.init(node, props);
}
