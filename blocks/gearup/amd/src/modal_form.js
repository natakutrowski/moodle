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
 * Modal form.
 *
 * @module     block_gearup/modal_form
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Str from 'core/str';
import Notification from 'core/notification';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import * as Compat from 'block_gearup/compat';
import ModalForm from 'core_form/modalform';
import {smartRefreshFromNode} from 'block_gearup/refreshable';
import * as RoleButton from 'block_gearup/role_button';
import {extractNodeData} from 'block_gearup/utils';

const getButton = (modalForm, action) => {
    const saveBtnJq = modalForm.modal.getFooter().find(modalForm.modal.getActionSelector(action));
    return saveBtnJq.length ? saveBtnJq[0] : null;
};

/**
 * Open the modal.
 *
 * @param {Node} node The node.
 */
async function open(node) {
    const formClass = node.dataset.formClass;
    const formArgs = extractNodeData(node, 'formArgs');
    const modalConfig = extractNodeData(node, 'modal');
    const canAttemptSmartRefresh = !formArgs.redirecturl;

    if (typeof formArgs.gupagectxid === 'undefined') {
        const pageParams = new URLSearchParams(window.location.search);
        if (pageParams.has('gupagectxid')) {
            formArgs.gupagectxid = pageParams.get('gupagectxid');
        }
    }

    const finalModalConfig = {
        template: 'block_gearup/modal_form',
        title: modalConfig.title,
    };
    if ('large' in modalConfig) {
        finalModalConfig.large = modalConfig.large === 'true';
    }

    var modalForm = new ModalForm({
        formClass: formClass,
        args: formArgs,
        returnFocus: node,
        modalConfig: finalModalConfig,
        moduleName: 'core/modal_save_cancel',
    });
    modalForm.addEventListener(modalForm.events.LOADED, () => {
        const root = modalForm.modal.getRoot();
        root.addClass('block_gearup');

        // Set the save button text.
        const saveBtn = getButton(modalForm, 'save');
        if (saveBtn && modalConfig.buttons?.save) {
            if (modalConfig.buttons.save?.label) {
                saveBtn.textContent = modalConfig.buttons.save?.label;
            }
            if (modalConfig.buttons.save?.danger) {
                saveBtn.classList.remove('btn-primary');
                saveBtn.classList.add('btn-danger');
            }
        }

        root.on(ModalEvents.bodyRendered, () => {

            const form = Compat.getFormNode(modalForm);
            const supportsDelete = Boolean(form.dataset.guSupportsDelete);
            if (!supportsDelete) {
                return;
            }

            const deleteBtnJq = modalForm.modal.getFooter().find(modalForm.modal.getActionSelector('delete'));
            const deleteBtn = deleteBtnJq.length ? deleteBtnJq[0] : null;
            if (!deleteBtn) {
                return;
            }

            deleteBtn.style.display = '';
            deleteBtn.addEventListener('click', () => {

                Compat.createModal({
                    body: Str.get_string('reallydeletethis', 'block_gearup'),
                    title: Str.get_string('confirm', 'core'),
                    buttons: {
                        save: Str.get_string('yesdelete', 'block_gearup'),
                        cancel: Str.get_string('cancel', 'core')
                    },
                    removeOnClose: true,
                }, ModalSaveCancel).then((modal) => {
                    modal.getFooter().find(modal.getActionSelector('save'))[0].classList.add('btn-danger');
                    modal.getRoot().on(ModalEvents.save, () => {

                        // Add delete flag.
                        const dodelete = document.createElement('input');
                        dodelete.type = 'hidden';
                        dodelete.name = '__gu_do_delete';
                        dodelete.value = '1';
                        form.appendChild(dodelete);

                        // Manually trigger the submission.
                        const submitter = document.createElement('input');
                        submitter.type = 'submit';
                        submitter.hidden = true;
                        form.appendChild(submitter);
                        submitter.click();
                    });
                    modal.show();
                    return;
                }).catch(Notification.exception);
            });
        });
    });

    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
        e.preventDefault();

        // We must mark the form as submitted because the core modalform sets it back
        // to not having been sent, and we'll reload the page so when the form only
        // has one field, this may be an issue.
        Compat.markFormSubmitted(Compat.getFormNode(modalForm));

        // If we can perform smart refresh, just hide.
        let smartRefreshing = canAttemptSmartRefresh ? smartRefreshFromNode(node, true) : null;
        if (smartRefreshing) {
            modalForm.modal.hide();
            return;
        }

        if (e.detail && e.detail.redirecturl) {
            window.location.href = e.detail.redirecturl;
        } else {
            window.location.reload();
        }

        // We hide the modal after a little while in case we stayed on the page.
        setTimeout(() => {
            modalForm.modal.hide();
        }, 1000);
    });

    modalForm.show();
}

/**
 * Delegate open.
 * @param {String} rootSelector The root selector.
 * @param {String} selector The selector.
 */
export function delegateOpen(rootSelector, selector) {
    RoleButton.delegateClick(rootSelector, selector, (node) => {
        open(node);
    });
}

/**
 * Register open.
 * @param {String} selector The selector.
 */
export function registerOpen(selector) {
    RoleButton.registerClick(selector, (node) => {
        open(node);
    });
}


let simpleOpenFormActionObserverRegistered = false;
const simpleOpenFormActionObserverSelector = '[data-gu-action="open-form"][data-form-class]';

/**
 * Register simple open form action observer.
 */
export function registerSimpleOpenFormActionObserver() {
    if (simpleOpenFormActionObserverRegistered) {
        return;
    }
    simpleOpenFormActionObserverRegistered = true;
    delegateOpen('body', simpleOpenFormActionObserverSelector);
}
