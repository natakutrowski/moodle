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
 * React app launcher.
 *
 * @module     block_gearup/react-launcher
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import ModalEvents from 'core/modal_events';
import ModalFactory from 'core/modal_factory';
import Pending from 'core/pending';
import * as Compat from 'block_gearup/compat';
import * as RoleButton from 'block_gearup/role_button';
import {extractNodeData} from 'block_gearup/utils';

/**
 * App launcher.
 *
 * @param {String} mod The module name.
 * @param {String} rootId The root ID.
 * @param {String} propsId The props ID.
 * @param {HTMLElement} [originatingNode] The originating node.
 */
export function launch(mod, rootId, propsId, originatingNode) {
    const props = JSON.parse(document.getElementById(propsId).textContent);
    launcherWithProps(mod, rootId, props, originatingNode);
}

/**
 * App launcher in modal.
 *
 * @param {String} mod The module name.
 * @param {String} propsId The props ID.
 * @param {Object} modalConfig The modal config.
 * @param {HTMLElement} [originatingNode] The originating node.
 * @returns {Promise}
 */
export async function launchInModal(mod, propsId, modalConfig = {}, originatingNode) {
    const props = JSON.parse(document.getElementById(propsId).textContent);
    launchInModalWithProps(mod, props, modalConfig, originatingNode);
}

/**
 * App launcher in modal with props.
 *
 * @param {String} mod The module name.
 * @param {String} props The props.
 * @param {Object} modalConfig The modal config.
 * @param {HTMLElement} [originatingNode] The originating node.
 * @returns {Promise}
 */
export async function launchInModalWithProps(mod, props, modalConfig = {}, originatingNode) {
    const id = `gu-react-launcher-in-modal-${Date.now()}`;
    const pendingBody = $.Deferred();
    const modal = await ModalFactory.create(Compat.patchModalConfig({
        type: ModalFactory.types.SAVE_CANCEL,
        removeOnClose: true,
        ...modalConfig,
        body: pendingBody,
    }));
    modal.getRoot().addClass('block_gearup');

    // Keep the React node height in sync with the modal body to avoid for the modal
    // to become scrollable. This is required because our current modal content is
    // absolute and thus requires a hardcoded height.
    const updateReactNodeHeight = () => {
        const body = modal.getBody()[0];
        const reactNode = document.getElementById(id);
        if (!body || !reactNode) {
            return;
        }
        const height = body.clientHeight - (parseFloat(getComputedStyle(body).paddingTop)
            + parseFloat(getComputedStyle(body).paddingBottom));
        reactNode.style.height = `${height}px`;
    };

    // Register resize events.
    modal.getRoot().on(ModalEvents.shown, () => {
        window.addEventListener('resize', updateReactNodeHeight);
    });
    modal.getRoot().on(ModalEvents.hidden, () => {
        window.removeEventListener('resize', updateReactNodeHeight);
    });

    // Trigger to show.
    modal.show();

    // Load the dependencies.
    const {startModalApp} = await loadModule(mod);

    // Execute the React app when the body is loaded.
    modal.getRoot().on(ModalEvents.bodyRendered, () => {
        updateReactNodeHeight();
        startModalApp(modal, document.getElementById(id), props, originatingNode);
    });

    // Once loaded, swap for our React div.
    pendingBody.resolve(`<div class="gu-h-[500px] gu-w-full gu-max-h-full gu-max-w-full" id="${id}"></div>`);
}

/**
 * App launcher with props.
 *
 * @param {String} mod The module name.
 * @param {String} rootId The root ID.
 * @param {Object} props The props.
 * @param {HTMLElement} [originatingNode] The originating node.
 */
export async function launcherWithProps(mod, rootId, props = {}, originatingNode) {
    const {startApp} = await loadModule(mod);
    startApp(document.getElementById(rootId), props, originatingNode);
}

/**
 * Load the module.
 *
 * @param {String} mod The react module.
 * @returns {Promise} Resolving with the module exported values.
 */
async function loadModule(mod) {
    const loader = $.Deferred();
    const pending = new Pending('block_gearup/react-launcher:launch');

    // Load the app module. By convension a module app needs to return
    // an object with (at least) two properties: `dependencies`, and `startApp`.
    require([mod], function(mod) {
        let dependencies = [];
        let dependenciesLoadedCallback = () => {
            return;
        };

        // If the module defines dependencies, set them up..
        if (mod.dependencies) {
            dependencies = mod.dependencies.list;
            dependenciesLoadedCallback = mod.dependencies.loader;
        }

        // Load the dependencies.
        require([].concat(dependencies), function() {
            const deps = [].slice.call(arguments);

            // Once the deps are loaded, pass them to the the app.
            dependenciesLoadedCallback(deps);

            loader.resolve(mod);
        });
    });

    return loader.then((mod) => {
        pending.resolve();
        return mod;
    });
}

/**
 * App launcher with props.
 *
 * @param {String} selector The selector.
 * @param {String} subSelector The sub selector.
 */
export function registerLaunchInModal(selector, subSelector) {
    const listener = (node) => {
        const mod = node.dataset.mod;
        const props = extractNodeData(node, 'props');
        const modalConfig = extractNodeData(node, 'modal');
        launchInModalWithProps(mod, props, modalConfig, node);
    };
    if (subSelector) {
        RoleButton.delegateClick(selector, subSelector, listener);
    } else {
        RoleButton.registerClick(selector, listener);
    }
}
