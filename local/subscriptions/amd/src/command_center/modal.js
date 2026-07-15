/* eslint-env amd */
define([
    'local_subscriptions/command_center/render',
    'local_subscriptions/command_center/feedback'
], function(Render, Feedback) {
    'use strict';

    function open(state, setActive) {
        state.previousFocus = document.activeElement;

        state.modal.hidden = false;
        state.modal.classList.remove('d-none');
        state.modal.classList.add('is-open');
        state.modal.setAttribute('aria-hidden', 'false');

        Render.showInitialState(state, setActive);

        window.setTimeout(function() {
            state.input.focus();
            state.input.select();
        }, 0);
    }

    function close(state) {
        if (Feedback.isDialogOpen(state)) {
            Feedback.closeDialog(state);
        }
        state.modal.classList.remove('is-open');
        state.modal.classList.add('d-none');
        state.modal.hidden = true;
        state.modal.setAttribute('aria-hidden', 'true');

        state.input.value = '';
        state.lastQuery = '';
        state.rememberedQuery = '';

        if (state.controller) {
            state.controller.abort();
            state.controller = null;
        }

        Render.clearResults(state);

        state.activeMenuIndex = -1;
        state.activeMenuItemIndex = -1;

        if (state.previousFocus && typeof state.previousFocus.focus === 'function') {
            state.previousFocus.focus();
        }

        if (state.notificationTimer) {
            window.clearTimeout(state.notificationTimer);
            state.notificationTimer = null;
        }

        if (state.notification) {
            state.notification.hidden = true;
            state.notification.textContent = '';
        }      
    }

    function isOpen(state) {
        return state.modal.classList.contains('is-open') &&
            !state.modal.classList.contains('d-none');
    }

    function toggle(state, setActive) {
        if (isOpen(state)) {
            close(state);
            return;
        }

        open(state, setActive);
    }

    return {
        open: open,
        close: close,
        toggle: toggle,
        isOpen: isOpen
    };
});