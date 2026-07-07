/* eslint-env amd */
define([
    'local_subscriptions/command_center/storage',
    'local_subscriptions/command_center/utils'
], function(Storage, Utils) {
    'use strict';

    var GROUP_ORDER = [
        'intent',
        'favorites',
        'recent',
        'actions',
        'users',
        'products',
        'purchases',
        'subscriptions',
        'default'
    ];

    function clearResults(state) {
        state.results.innerHTML = '';
        state.items = [];
        state.activeIndex = -1;
        state.input.removeAttribute('aria-activedescendant');
    }

    function showMessage(state, message) {
        state.items = [];
        state.activeIndex = -1;
        state.results.innerHTML = '<div class="campusfr-command-empty">' + Utils.escapeHtml(message) + '</div>';
        state.input.removeAttribute('aria-activedescendant');
    }

    function showInitialState(state, setActive) {
        var favorites = Storage.clean(
            Storage.read(Storage.keys.favorites),
            Storage.limits.favorites,
            'favorites'
        );

        var recent = Storage.clean(
            Storage.read(Storage.keys.recent),
            Storage.limits.recent,
            'recent'
        );

        favorites = favorites.map(function(item) {
            item.groupLabel = state.favoriteLabel;
            return item;
        });

        recent = recent.filter(function(item) {
            return !favorites.some(function(favorite) {
                return favorite.url === item.url;
            });
        });

        recent = recent.map(function(item) {
            item.groupLabel = state.recentLabel;
            return item;
        });

        if (!favorites.length && !recent.length) {
            showMessage(state, state.initialLabel);
            return;
        }

        renderResults(state, favorites.concat(recent), setActive);
    }

    function renderResults(state, items, setActive) {
        state.items = items || [];
        state.activeIndex = -1;
        state.activeMenuIndex = -1;
        state.activeMenuItemIndex = -1;

        if (!state.items.length) {
            showMessage(state, state.emptyLabel);
            return;
        }

        state.items = sortItemsByGroup(state.items);
        state.results.innerHTML = renderGroupedResults(state.items, state);

        if (typeof setActive === 'function') {
            setActive(state, 0);
        }
    }

    function sortItemsByGroup(items) {
        return items.slice().sort(function(a, b) {
            var groupA = GROUP_ORDER.indexOf(a.group || 'default');
            var groupB = GROUP_ORDER.indexOf(b.group || 'default');

            if (groupA === -1) {
                groupA = GROUP_ORDER.length;
            }

            if (groupB === -1) {
                groupB = GROUP_ORDER.length;
            }

            if (groupA !== groupB) {
                return groupA - groupB;
            }

            return (b.score || 0) - (a.score || 0);
        });
    }

    function renderGroupedResults(items, state) {
        var currentGroup = null;
        var html = '';

        items.forEach(function(item, index) {
            var group = item.group || 'default';
            var groupLabel = item.groupLabel || item.type || '';

            if (group !== currentGroup) {
                currentGroup = group;
                html += renderGroupHeader(group, groupLabel, state);
            }

            item.id = state.resultIdPrefix + '-' + index;
            html += renderResult(item, index, shouldShowBestMatch(state, item, index), state.bestLabel, state);
        });

        return html;
    }

    function renderGroupHeader(group, groupLabel, state) {
        return '' +
            '<div class="campusfr-command-group" role="presentation">' +
                '<div class="campusfr-command-group-label">' +
                    '<span>' + Utils.escapeHtml(groupLabel) + '</span>' +
                    renderGroupAction(group, state) +
                '</div>' +
            '</div>';
    }

    function renderGroupAction(group, state) {
        if (group !== 'recent') {
            return '';
        }

        return '' +
            '<button class="campusfr-command-group-action campusfr-command-clear-recent" type="button">' +
                Utils.escapeHtml(state.clearRecentLabel) +
            '</button>';
    }

    function shouldShowBestMatch(state, item, index) {
        if (index !== 0) {
            return false;
        }

        if (!state.input.value.trim()) {
            return false;
        }

        return item.group !== 'favorites' && item.group !== 'recent';
    }

    function renderResult(item, index, isBestMatch, bestLabel, state) {
        return '' +
            '<a id="' + Utils.escapeHtml(item.id || '') + '" class="campusfr-command-result' + (item.danger ? ' is-danger' : '') + '" href="' + Utils.escapeHtml(item.url || '#') + '" data-index="' + index + '" role="option" aria-selected="false">' +
                '<span class="campusfr-command-result-icon">' + Utils.escapeHtml(item.icon) + '</span>' +
                '<span class="campusfr-command-result-body">' +
                    '<span class="campusfr-command-result-title">' + Utils.escapeHtml(item.title) + renderBestMatch(isBestMatch, bestLabel) + '</span>' +
                    '<span class="campusfr-command-result-subtitle">' + Utils.escapeHtml(item.subtitle) + '</span>' +
                '</span>' +
                '<span class="campusfr-command-result-meta">' +
                    renderFavorite(item, state) +
                    renderResultAction(item) +
                    renderResultShortcut(item) +
                    renderMenuToggle(item) +
                '</span>' +
                renderMenu(item) +
            '</a>';
    }

    function renderFavorite(item, state) {
        var favorite = Storage.isFavorite(item);

        return '' +
            '<button class="campusfr-command-favorite' + (favorite ? ' is-favorite' : '') + '"' +
            ' data-url="' + Utils.escapeHtml(item.url || '') + '"' +
            ' type="button"' +
            ' title="' + Utils.escapeHtml(state.favoriteTitle) + '"' +
            ' aria-label="' + Utils.escapeHtml(state.favoriteTitle) + '"' +
            ' aria-pressed="' + (favorite ? 'true' : 'false') + '">' +
            (favorite ? '★' : '☆') +
            '</button>';
    }

    function renderBestMatch(isBestMatch, bestLabel) {
        if (!isBestMatch) {
            return '';
        }

        return '<span class="campusfr-command-best-match">' + Utils.escapeHtml(bestLabel) + '</span>';
    }

    function renderResultAction(item) {
        if (!item.actionLabel) {
            return '<span class="campusfr-command-result-type">' + Utils.escapeHtml(item.type) + '</span>';
        }

        return '<span class="campusfr-command-result-action">' + Utils.escapeHtml(item.actionLabel) + '</span>';
    }

    function renderResultShortcut(item) {
        if (!item.shortcut) {
            return '';
        }

        return '<span class="campusfr-command-result-shortcut">' + Utils.escapeHtml(item.shortcut) + '</span>';
    }

    function renderMenuToggle(item) {
        if (!item.menuItems || !item.menuItems.length) {
            return '';
        }

        return '' +
            '<button class="campusfr-command-menu-toggle" ' +
                'type="button" ' +
                'aria-label="Actions" ' +
                'aria-haspopup="menu" ' +
                'aria-expanded="false">' +
                '⋯' +
            '</button>';
    }

    function renderMenu(item) {
        if (!item.menuItems || !item.menuItems.length) {
            return '';
        }

        var html = '<div class="campusfr-command-result-menu" hidden>';

        item.menuItems.forEach(function(menuItem, index) {
            html += '' +
                '<button class="campusfr-command-menu-item' + (menuItem.danger ? ' is-danger' : '') + '" type="button" data-menu-index="' + index + '">' +
                    '<span class="campusfr-command-menu-icon">' + Utils.escapeHtml(menuItem.icon || '') + '</span>' +
                    '<span class="campusfr-command-menu-label">' + Utils.escapeHtml(menuItem.label || '') + '</span>' +
                    renderMenuShortcut(menuItem) +
                '</button>';
        });

        html += '</div>';

        return html;
    }

    function renderMenuShortcut(menuItem) {
        if (!menuItem.shortcut) {
            return '';
        }

        return '<span class="campusfr-command-menu-shortcut">Alt+' + Utils.escapeHtml(menuItem.shortcut) + '</span>';
    }

    return {
        clearResults: clearResults,
        showMessage: showMessage,
        showInitialState: showInitialState,
        renderResults: renderResults
    };
});