/* eslint-env amd */
define([], function() {
    'use strict';

    function ensureDialog(state) {
        if (state.dialog) {
            return;
        }

        state.dialog = document.createElement('div');
        state.dialog.className = 'campusfr-command-dialog';
        state.dialog.hidden = true;
        state.dialog.setAttribute('role', 'dialog');
        state.dialog.setAttribute('aria-modal', 'true');

        state.dialog.innerHTML = '' +
            '<div class="campusfr-command-dialog-panel">' +
                '<div class="campusfr-command-dialog-title"></div>' +
                '<div class="campusfr-command-dialog-message"></div>' +
                '<div class="campusfr-command-dialog-actions">' +
                    '<button type="button" class="btn btn-secondary campusfr-command-dialog-cancel"></button>' +
                    '<button type="button" class="btn btn-primary campusfr-command-dialog-confirm"></button>' +
                '</div>' +
            '</div>';

        state.modal.appendChild(state.dialog);

        state.dialogTitle = state.dialog.querySelector('.campusfr-command-dialog-title');
        state.dialogMessage = state.dialog.querySelector('.campusfr-command-dialog-message');
        state.dialogConfirm = state.dialog.querySelector('.campusfr-command-dialog-confirm');
        state.dialogCancel = state.dialog.querySelector('.campusfr-command-dialog-cancel');

        state.dialogConfirm.addEventListener('click', function() {
            confirmDialog(state);
        });

        state.dialogCancel.addEventListener('click', function() {
            closeDialog(state);
        });

        state.dialog.addEventListener('click', function(e) {
            if (e.target === state.dialog) {
                closeDialog(state);
            }
        });

        state.dialog.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeDialog(state);
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                confirmDialog(state);
            }
        });
    }

    function ensureNotification(state) {
        if (state.notification) {
            return;
        }

        state.notification = document.createElement('div');
        state.notification.className = 'campusfr-command-notification';
        state.notification.hidden = true;

        state.modal.appendChild(state.notification);
    }

    function notify(state, message, type) {
        ensureNotification(state);

        window.clearTimeout(state.notificationTimer);

        state.notification.textContent = message || '';
        state.notification.classList.remove('is-error', 'is-success');
        state.notification.classList.add(type === 'error' ? 'is-error' : 'is-success');
        state.notification.hidden = false;

        state.notificationTimer = window.setTimeout(function() {
            state.notification.hidden = true;
        }, 2500);
    }

    function alertError(state, message) {
        notify(state, message || state.actionFailedLabel, 'error');
    }

    function confirm(state, message, onConfirm, options) {
        options = options || {};

        ensureDialog(state);

        state.pendingAction = onConfirm;
        state.previousDialogFocus = document.activeElement;

        state.dialogTitle.textContent = options.title || (options.danger ? state.dangerConfirmLabel : state.confirmLabel);
        state.dialogMessage.textContent = message || state.confirmLabel;
        state.dialogConfirm.textContent = options.confirmLabel || state.confirmLabel;
        state.dialogCancel.textContent = options.cancelLabel || state.cancelLabel;

        state.dialog.classList.toggle('is-danger', !!options.danger);
        state.dialog.hidden = false;

        window.setTimeout(function() {
            state.dialogConfirm.focus();
        }, 0);
    }

    function confirmDialog(state) {
        var action = state.pendingAction;

        closeDialog(state);

        if (typeof action === 'function') {
            action();
        }
    }

    function closeDialog(state) {
        if (!state.dialog) {
            return;
        }

        state.dialog.hidden = true;
        state.dialog.classList.remove('is-danger');
        state.pendingAction = null;

        if (state.previousDialogFocus && typeof state.previousDialogFocus.focus === 'function') {
            state.previousDialogFocus.focus();
        }

        state.previousDialogFocus = null;
    }

    function isDialogOpen(state) {
        return !!(state.dialog && !state.dialog.hidden);
    }

    return {
        alertError: alertError,
        confirm: confirm,
        closeDialog: closeDialog,
        isDialogOpen: isDialogOpen,
        notify: notify
    };
});