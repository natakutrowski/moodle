/* eslint-env amd */
define([
    'local_subscriptions/command_center/constants',
    'local_subscriptions/command_center/state',
    'local_subscriptions/command_center/storage',
    'local_subscriptions/command_center/events'
], function(Constants, State, Storage, Events) {
    'use strict';

    function init() {
        window.setTimeout(function() {
            Storage.cleanup();

            document.querySelectorAll(Constants.selectors.root).forEach(function(root) {
                if (root.dataset.commandCenterReady === '1') {
                    return;
                }

                root.dataset.commandCenterReady = '1';
                boot(root);
            });
        }, 100);
    }

    function boot(root) {
        var state = State.create(root, Constants.selectors);

        if (!State.isValid(state)) {
            return;
        }

        Events.bind(state, Constants.selectors);
    }

    return {
        init: init
    };
});