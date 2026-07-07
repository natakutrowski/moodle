/* eslint-env amd */
define([
    'local_subscriptions/command_center/storage',
    'local_subscriptions/command_center/utils',
    'local_subscriptions/command_center/render',
    'local_subscriptions/command_center/search',
    'local_subscriptions/command_center/actions',
    'local_subscriptions/command_center/keyboard',
    'local_subscriptions/command_center/modal'
], function(Storage, Utils, Render, Search, Actions, Keyboard, Modal) {
    'use strict';

    function bind(state, selectors) {
        state.trigger.addEventListener('click', function() {
            Modal.open(state, Keyboard.setActive);
        });

        state.trigger.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                Modal.open(state, Keyboard.setActive);
            }
        });

        if (state.backdrop) {
            state.backdrop.addEventListener('click', function() {
                Modal.close(state);
            });
        }

        if (state.closeButton) {
            state.closeButton.addEventListener('click', function() {
                Modal.close(state);
            });
        }

        state.input.addEventListener('input', function() {
            if (state.pendingAction) {
                return;
            }
            closeAllMenus(state, selectors);
            Search.debounce(state, Keyboard.setActive);
        });

        state.input.addEventListener('keydown', function(e) {
            Keyboard.handleKeydown(state, e, Modal.close);
        });

        state.results.addEventListener('mousemove', function(e) {
            var row = e.target.closest(selectors.result);

            if (!row) {
                return;
            }

            Keyboard.setActive(state, Utils.getItemIndexFromRow(row));
        });

        state.results.addEventListener('click', function(e) {
            handleResultsClick(state, selectors, e);
        });

        bindGlobalShortcut(state);

        document.addEventListener('click', function(e) {
            if (!state.modal || !Modal.isOpen(state)) {
                return;
            }

            if (state.results.contains(e.target)) {
                return;
            }

            closeAllMenus(state, selectors);
        });
    }

    function handleResultsClick(state, selectors, e) {
        var clearRecent = e.target.closest(selectors.clearRecent);

        if (clearRecent) {
            e.preventDefault();
            e.stopPropagation();

            Storage.write(Storage.keys.recent, []);
            Render.showInitialState(state, Keyboard.setActive);

            return;
        }

        var menuToggle = e.target.closest('.campusfr-command-menu-toggle');

        if (menuToggle) {
            e.preventDefault();
            e.stopPropagation();

            toggleMenu(state, selectors, menuToggle);
            return;
        }

        var menuItem = e.target.closest(selectors.menuItem);

        if (menuItem) {
            e.preventDefault();
            e.stopPropagation();

            executeMenuItem(state, selectors, menuItem);
            return;
        }

        var favorite = e.target.closest(selectors.favorite);

        if (favorite) {
            handleFavoriteClick(state, selectors, e, favorite);
            return;
        }

        Actions.openActive(state);
    }

    function handleFavoriteClick(state, selectors, e, favorite) {
        e.preventDefault();
        e.stopPropagation();

        var row = favorite.closest(selectors.result);

        if (!row) {
            return;
        }

        var index = Utils.getItemIndexFromRow(row);

        if (index < 0 || !state.items[index]) {
            return;
        }

        Storage.toggleFavorite(state.items[index]);

        Render.renderResults(state, state.items, Keyboard.setActive);
        Keyboard.setActive(state, index);
    }

    function bindGlobalShortcut(state) {
        if (state.globalShortcutBound) {
            return;
        }

        state.globalShortcutBound = true;

        document.addEventListener('keydown', function(e) {
            handleGlobalShortcut(e, state);
        });
    }

    function handleGlobalShortcut(e, state) {
        var key = String(e.key || '').toLowerCase();
        var isShortcut = ((e.metaKey || e.ctrlKey) && key === 'k') ||
            (e.ctrlKey && e.altKey && key === 'k');

        if (!isShortcut) {
            return;
        }

        e.preventDefault();

        if (Modal.isOpen(state)) {
            Modal.close(state);
        } else {
            Modal.open(state, Keyboard.setActive);
        }
    }

    function toggleMenu(state, selectors, toggle) {
        var row = toggle.closest(selectors.result);

        if (!row) {
            return;
        }

        var menu = row.querySelector(selectors.menu);

        if (!menu) {
            return;
        }

        closeAllMenus(state, selectors, menu);

        var willOpen = menu.hidden;

        menu.hidden = !willOpen;
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    function closeAllMenus(state, selectors, except) {
        state.results.querySelectorAll(selectors.menu).forEach(function(menu) {
            if (menu !== except) {
                menu.hidden = true;
            }
        });

        state.results.querySelectorAll('.campusfr-command-menu-toggle').forEach(function(toggle) {
            var row = toggle.closest(selectors.result);
            var menu = row ? row.querySelector(selectors.menu) : null;

            toggle.setAttribute('aria-expanded', menu && !menu.hidden ? 'true' : 'false');
        });
    }

    function executeMenuItem(state, selectors, menuItemButton) {
        var row = menuItemButton.closest(selectors.result);

        if (!row) {
            return;
        }

        var itemIndex = Utils.getItemIndexFromRow(row);
        var menuIndex = parseInt(menuItemButton.getAttribute('data-menu-index'), 10);

        if (itemIndex < 0 || isNaN(menuIndex)) {
            return;
        }

        var item = state.items[itemIndex];

        if (!item || !item.menuItems || !item.menuItems[menuIndex]) {
            return;
        }

        Actions.executeMenuItem(state, item, item.menuItems[menuIndex]);
        closeAllMenus(state, selectors);
    }

    return {
        bind: bind
    };
});