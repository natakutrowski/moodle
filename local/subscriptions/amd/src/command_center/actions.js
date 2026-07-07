/* eslint-env amd */
define([
    'core/config',
    'local_subscriptions/command_center/storage',
    'local_subscriptions/command_center/feedback'
], function(Config, Storage, Feedback) {
    'use strict';

    function getActiveItem(state) {
        if (state.activeIndex < 0) {
            return null;
        }

        return state.items[state.activeIndex] || null;
    }

    function openItem(item) {
        if (!item || !item.url) {
            return;
        }

        Storage.rememberRecent(item);
        window.location.href = item.url;
    }

    function executeItem(state, item) {
        if (!item || state.isExecuting) {
            return;
        }

        if (item.fillQuery) {
            fillQuery(state, item.fillQuery);
            return;
        }

        if (!item.actionKey) {
            if (item.url && item.url !== '#') {
                openItem(item);
            }
            return;
        }

        if (item.requiresConfirmation) {
            Feedback.confirm(state, item.confirmMessage, function() {
                execute(state, item.actionKey, item.payload || {}, item);
            }, {
                danger: !!item.danger
            });
            return;
        }

        execute(state, item.actionKey, item.payload || {}, item);
    }

    function execute(state, actionKey, payload, item) {
        if (!state.executeUrl) {
            openItem(item);
            return;
        }

        state.isExecuting = true;
        state.executingItemKey = actionKey;
        setBusy(state, true);

        var formData = new FormData();

        formData.append('sesskey', Config.sesskey);
        formData.append('action', actionKey);
        formData.append('payload', JSON.stringify(payload || {}));

        fetch(state.executeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Invalid response');
                }

                return response.json();
            })
            .then(function(data) {
                if (!data || data.success === false) {
                    Feedback.alertError(state, (data && data.message) || state.actionFailedLabel);
                    return;
                }

                Storage.rememberRecent(item);

                if (data.message && !data.redirectUrl) {
                    Feedback.notify(state, data.message, 'success');
                }

                if (data.redirectUrl) {
                    window.location.href = data.redirectUrl;
                    return;
                }

                if (data.refresh) {
                    window.setTimeout(function() {
                        window.location.reload();
                    }, data.message ? 900 : 0);
                }
            })
            .catch(function() {
                Feedback.alertError(state, state.actionErrorLabel);
            })
            .finally(function() {
                state.isExecuting = false;
                state.executingItemKey = '';
                setBusy(state, false);
            });
    }

    function setBusy(state, busy) {
        if (!state.modal) {
            return;
        }

        state.modal.classList.toggle('is-busy', busy);
        state.input.disabled = busy;

        state.results.querySelectorAll('button').forEach(function(button) {
            button.disabled = busy;
        });
    }

    function openActive(state) {
        executeItem(state, getActiveItem(state));
    }

    function executeMenuItem(state, item, menuItem) {
        if (!item || !menuItem || state.isExecuting) {
            return;
        }

        if (menuItem.requiresConfirmation) {
            Feedback.confirm(state, menuItem.confirmMessage, function() {
                execute(state, menuItem.actionKey, menuItem.payload || {}, item);
            }, {
                danger: !!menuItem.danger
            });
            return;
        }

        execute(state, menuItem.actionKey, menuItem.payload || {}, item);
    }

    function fillQuery(state, query) {
        state.input.value = query;
        state.input.focus();

        if (typeof state.input.setSelectionRange === 'function') {
            state.input.setSelectionRange(query.length, query.length);
        }

        state.lastQuery = '';

        state.input.dispatchEvent(new Event('input', {
            bubbles: true
        }));
    }

    return {
        getActiveItem: getActiveItem,
        openItem: openItem,
        executeItem: executeItem,
        execute: execute,
        openActive: openActive,
        executeMenuItem: executeMenuItem,
        setBusy: setBusy,
        fillQuery: fillQuery
    };
});