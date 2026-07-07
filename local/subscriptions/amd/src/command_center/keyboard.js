/* eslint-env amd */
define([
    'local_subscriptions/command_center/actions',
    'local_subscriptions/command_center/feedback'
], function(Actions, Feedback) {
    'use strict';

    function setActive(state, index) {
        var rows = state.results.querySelectorAll('.campusfr-command-result');

        if (!rows.length) {
            state.activeIndex = -1;
            state.input.removeAttribute('aria-activedescendant');
            return;
        }

        if (index < 0) {
            index = rows.length - 1;
        }

        if (index >= rows.length) {
            index = 0;
        }

        rows.forEach(function(row) {
            row.classList.remove('is-active');
            row.setAttribute('aria-selected', 'false');
        });

        rows[index].classList.add('is-active');
        rows[index].setAttribute('aria-selected', 'true');
        rows[index].scrollIntoView({block: 'nearest'});

        state.activeIndex = index;
        closeMenus(state);
        state.input.setAttribute('aria-activedescendant', rows[index].id);
    }

    function moveActive(state, delta) {
        setActive(state, state.activeIndex + delta);
    }

    function pageActive(state, delta) {
        moveActive(state, delta * 5);
    }

    function handleKeydown(state, event, closeModal) {
        if (Feedback.isDialogOpen(state)) {
            return;
        }

        if (event.altKey && event.key === 'Enter') {
            event.preventDefault();
            openActiveMenu(state);
            return;
        }

        if (handleMenuShortcut(state, event)) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                moveActive(state, 1);
                break;

            case 'ArrowUp':
                event.preventDefault();
                moveActive(state, -1);
                break;

            case 'Home':
                event.preventDefault();
                setActive(state, 0);
                break;

            case 'End':
                event.preventDefault();
                setActive(state, state.items.length - 1);
                break;

            case 'PageDown':
                event.preventDefault();
                pageActive(state, 1);
                break;

            case 'PageUp':
                event.preventDefault();
                pageActive(state, -1);
                break;

            case 'Enter':
                event.preventDefault();
                Actions.openActive(state);
                break;

            case 'Escape':
                event.preventDefault();

                if (Feedback.isDialogOpen(state)) {
                    Feedback.closeDialog(state);
                    return;
                }

                if (closeMenus(state)) {
                    return;
                }

                closeModal(state);
                break;

            case 'Tab':
                closeModal(state);
                break;
        }
    }

    function handleMenuShortcut(state, event) {
        if (!event.altKey || event.ctrlKey || event.metaKey) {
            return false;
        }

        var key = String(event.key || '').toUpperCase();

        if (!key || key.length !== 1) {
            return false;
        }

        var item = state.items[state.activeIndex];

        if (!item || !item.menuItems || !item.menuItems.length) {
            return false;
        }

        var menuItem = item.menuItems.find(function(entry) {
            return String(entry.shortcut || '').toUpperCase() === key;
        });

        if (!menuItem) {
            return false;
        }

        event.preventDefault();

        require(['local_subscriptions/command_center/actions'], function(Actions) {
            Actions.executeMenuItem(state, item, menuItem);
            closeMenus(state);
        });

        return true;
    }

    function closeMenus(state) {
        var menus = state.results.querySelectorAll('.campusfr-command-result-menu');
        var closed = false;

        menus.forEach(function(menu) {
            if (!menu.hidden) {
                menu.hidden = true;
                closed = true;
            }
        });

        state.activeMenuIndex = -1;
        state.activeMenuItemIndex = -1;

        return closed;
    }

    function openActiveMenu(state) {
        var rows = state.results.querySelectorAll('.campusfr-command-result');

        if (state.activeIndex < 0 || !rows[state.activeIndex]) {
            return;
        }

        var menu = rows[state.activeIndex].querySelector('.campusfr-command-result-menu');

        if (!menu) {
            return;
        }

        var willOpen = menu.hidden;
        var toggle = rows[state.activeIndex].querySelector('.campusfr-command-menu-toggle');

        menu.hidden = !willOpen;

        if (toggle) {
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        }

        state.activeMenuIndex = willOpen ? state.activeIndex : -1;
        state.activeMenuItemIndex = -1;
    }

    return {
        setActive: setActive,
        moveActive: moveActive,
        pageActive: pageActive,
        handleKeydown: handleKeydown,
        openActiveMenu: openActiveMenu,
        closeMenus: closeMenus,
        handleMenuShortcut: handleMenuShortcut
    };
});