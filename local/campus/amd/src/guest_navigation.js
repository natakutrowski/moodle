/* eslint-env amd */
define([], function() {
    'use strict';

    var SELECTOR = '[data-campus-guest-navigation]';

    function isMobile() {
        return window.matchMedia('(max-width: 767.98px)').matches;
    }

    function collapseActions(root, except) {
        root.querySelectorAll('[data-campus-guest-action]').forEach(function(action) {
            if (action === except) {
                return;
            }
            action.classList.remove('is-expanded');
            action.classList.add('is-compact');
        });
    }

    function bind(root) {
        if (root.dataset.campusGuestNavigationReady === '1') {
            return;
        }
        root.dataset.campusGuestNavigationReady = '1';

        root.querySelectorAll('[data-campus-guest-action][data-collapsible="1"]').forEach(function(action) {
            action.addEventListener('click', function(event) {
                if (!isMobile() || action.classList.contains('is-expanded')) {
                    return;
                }
                event.preventDefault();
                collapseActions(root, action);
                action.classList.remove('is-compact');
                action.classList.add('is-expanded');
                var language = root.querySelector('[data-campus-guest-language]');
                if (language) {
                    language.removeAttribute('open');
                }
            });
        });

        var language = root.querySelector('[data-campus-guest-language]');
        if (language) {
            language.addEventListener('toggle', function() {
                if (language.open && isMobile()) {
                    collapseActions(root, null);
                }
            });
        }
    }

    function init() {
        document.querySelectorAll(SELECTOR).forEach(bind);
    }

    return {init: init};
});
