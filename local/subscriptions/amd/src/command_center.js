/* eslint-env amd */
define([], function() {
    'use strict';

    var SELECTORS = {
        root: '.campusfr-command-center',
        trigger: '.campusfr-command-trigger',
        modal: '.campusfr-command-modal',
        input: '.campusfr-command-input',
        results: '.campusfr-command-results',
        backdrop: '.campusfr-command-backdrop',
        close: '.campusfr-command-close',
        result: '.campusfr-command-result'
    };

    var GROUP_ORDER = [
        'favorites',
        'recent',
        'actions',
        'users',
        'products',
        'purchases',
        'subscriptions',
        'default'
    ];

    var STORAGE_KEYS = {
        recent: 'campusfr_command_center_recent',
        favorites: 'campusfr_command_center_favorites'
    };

    var STORAGE_LIMITS = {
        recent: 10,
        favorites: 25
    }; 

    function init() {
        window.setTimeout(function() {
            document.querySelectorAll(SELECTORS.root).forEach(function(root) {
                if (root.dataset.commandCenterReady === '1') {
                    return;
                }

                root.dataset.commandCenterReady = '1';
                boot(root);
            });
        }, 100);
    }

    function boot(root) {
        var state = {
            searchUrl: root.getAttribute('data-search-url'),
            emptyLabel: root.getAttribute('data-empty-label') || 'No results',
            errorLabel: root.getAttribute('data-error-label') || 'Search error',
            loadingLabel: root.getAttribute('data-loading-label') || 'Loading…',
            trigger: root.querySelector(SELECTORS.trigger),
            modal: root.querySelector(SELECTORS.modal),
            input: root.querySelector(SELECTORS.input),
            results: root.querySelector(SELECTORS.results),
            backdrop: root.querySelector(SELECTORS.backdrop),
            closeButton: root.querySelector(SELECTORS.close),
            controller: null,
            debounceTimer: null,
            activeIndex: -1,
            items: [],
            lastQuery: '',
            initialLabel: root.getAttribute('data-initial-label') || 'Start typing to search',
            rememberedQuery: '',
            previousFocus: null,
            bestLabel: root.getAttribute('data-best-label') || 'Best',
            recentLabel: root.getAttribute('data-recent-label') || 'Recent',
            favoriteLabel: root.getAttribute('data-favorite-label') || 'Favorites',
            isOpening: false,
            globalShortcutBound: false,
            favoriteTitle: root.getAttribute('data-favorite-title') || 'Toggle favorite',
            clearRecentLabel: root.getAttribute('data-clear-recent-label') || 'Clear recent',
            resultIdPrefix: 'campusfr-command-result-' + Math.random().toString(36).slice(2)
        };

        if (!state.trigger || !state.modal || !state.input || !state.results) {
            return;
        }

        bindEvents(state);
    }

    function bindEvents(state) {
        state.trigger.addEventListener('click', function() {
            openPalette(state);
        });

        state.trigger.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openPalette(state);
            }
        });

        if (state.backdrop) {
            state.backdrop.addEventListener('click', function() {
                closePalette(state);
            });
        }

        if (state.closeButton) {
            state.closeButton.addEventListener('click', function() {
                closePalette(state);
            });
        }

        state.input.addEventListener('input', function() {
            debounceSearch(state);
        });

        state.input.addEventListener('keydown', function(e) {
            handleInputKeydown(e, state);
        });

        state.results.addEventListener('mousemove', function(e) {
            var row = e.target.closest(SELECTORS.result);

            if (!row) {
                return;
            }

            setActive(state, getItemIndexFromRow(row));
        });

        state.results.addEventListener('click', function(e) {

            var clearRecent = e.target.closest('.campusfr-command-clear-recent');

            if (clearRecent) {

                e.preventDefault();
                e.stopPropagation();

                writeStorage(STORAGE_KEYS.recent, []);
                showInitialState(state);

                return;
            }

            var favorite = e.target.closest('.campusfr-command-favorite');

            if (favorite) {

                e.preventDefault();
                e.stopPropagation();

                var row = favorite.closest(SELECTORS.result);

                if (!row) {
                    return;
                }

                var index = getItemIndexFromRow(row);

                if (index < 0 || !state.items[index]) {
                    return;
                }

                toggleFavorite(state.items[index]);

                renderResults(state, state.items);

                setActive(state, index);

                return;
            }

            openActive(state);
        });

        document.addEventListener('keydown', function(e) {
            handleGlobalShortcut(e, state);
        });
    }

    function openPalette(state) {
        if (isOpen(state) || state.isOpening) {
            return;
        }

        state.isOpening = true;

        state.previousFocus = document.activeElement;
        cleanupStorage();
        state.modal.classList.remove('d-none');
        state.modal.setAttribute('aria-hidden', 'false');

        state.input.value = state.rememberedQuery || '';
        state.lastQuery = '';

        if (state.input.value.trim().length >= 2) {
            search(state, state.input.value);
        } else {
            showInitialState(state);
        }

        window.setTimeout(function() {
            state.input.focus();
            state.input.select();
            state.isOpening = false;
        }, 50);
    }

    function showInitialState(state) {
        var favorites = cleanStoredItems(
            readStorage(STORAGE_KEYS.favorites),
            STORAGE_LIMITS.favorites,
            'favorites'
        );

        var recent = cleanStoredItems(
            readStorage(STORAGE_KEYS.recent),
            STORAGE_LIMITS.recent,
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

        renderResults(state, favorites.concat(recent));

    }


    function closePalette(state) {
        if (!isOpen(state)) {
            return;
        }
        state.modal.classList.add('d-none');
        state.modal.setAttribute('aria-hidden', 'true');

        state.rememberedQuery = state.input.value.trim();
        state.input.value = '';
        state.lastQuery = '';
        clearResults(state);

        if (state.controller) {
            state.controller.abort();
            state.controller = null;
        }

        if (state.previousFocus && typeof state.previousFocus.focus === 'function') {
            state.previousFocus.focus();
        }

        state.previousFocus = null;        
    }

    function isOpen(state) {
        return !state.modal.classList.contains('d-none');
    }

    function hasResults(state) {
        return state.items.length > 0;
    }

    function clearResults(state) {
        state.results.innerHTML = '';
        state.items = [];
        state.activeIndex = -1;
        state.input.removeAttribute('aria-activedescendant');
    }

    function handleInputKeydown(e, state) {
        if (e.key === 'Escape') {
            e.preventDefault();
            closePalette(state);
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(state, state.activeIndex + 1);
            return;
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(state, state.activeIndex - 1);
            return;
        }

        if (e.key === 'Tab') {
            e.preventDefault();
            setActive(state, e.shiftKey ? state.activeIndex - 1 : state.activeIndex + 1);
            return;
        }

        if (e.key === 'Home') {
            e.preventDefault();
            setActive(state, 0);
            return;
        }

        if (e.key === 'End') {
            e.preventDefault();
            setActive(state, state.items.length - 1);
            return;
        }

        if (e.key === 'PageDown') {
            e.preventDefault();
            setActive(state, state.activeIndex + 5);
            return;
        }

        if (e.key === 'PageUp') {
            e.preventDefault();
            setActive(state, state.activeIndex - 5);
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            openActive(state);
        }
    }

    function handleGlobalShortcut(e, state) {
        var key = String(e.key || '').toLowerCase();
        var isShortcut = ((e.metaKey || e.ctrlKey) && key === 'k') ||
            (e.ctrlKey && e.altKey && key === 'k');

        if (!isShortcut) {
            return;
        }

        e.preventDefault();

        if (isOpen(state)) {
            closePalette(state);
        } else {
            openPalette(state);
        }
    }

    function setActive(state, index) {
        if (!hasResults(state)) {
            state.activeIndex = -1;
            return;
        }

        var rows = state.results.querySelectorAll(SELECTORS.result);

        rows.forEach(function(row) {
            row.classList.remove('is-active');
            row.setAttribute('aria-selected', 'false');
        });

        if (!rows.length) {
            state.activeIndex = -1;
            return;
        }

        if (index < 0) {
            index = rows.length - 1;
        }

        if (index >= rows.length) {
            index = 0;
        }

        state.activeIndex = index;

        rows[state.activeIndex].classList.add('is-active');
        rows[state.activeIndex].setAttribute('aria-selected', 'true');
        state.input.setAttribute('aria-activedescendant', rows[state.activeIndex].id);
        rows[state.activeIndex].scrollIntoView({
            block: 'nearest'
        });
    }

    function openActive(state) {
        var item = getActiveItem(state);

        if (!item || !item.url) {
            return;
        }

        rememberRecent(item);
        window.location.href = item.url;
    }

    function debounceSearch(state) {
        window.clearTimeout(state.debounceTimer);

        state.debounceTimer = window.setTimeout(function() {
            search(state, state.input.value);
        }, 180);
    }

    function search(state, query) {
        query = query.trim();

        if (query === state.lastQuery) {
            return;
        }

        state.lastQuery = query;

        if (query.length < 2) {
            showInitialState(state);
            return;
        }

        if (!state.searchUrl) {
            showMessage(state, state.errorLabel);
            return;
        }
        
        showMessage(state, state.loadingLabel);

        if (state.controller) {
            state.controller.abort();
        }

        state.controller = new AbortController();

        fetch(state.searchUrl + '?q=' + encodeURIComponent(query), {
            method: 'GET',
            credentials: 'same-origin',
            signal: state.controller.signal
        })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Invalid response');
                }

                return response.json();
            })
            .then(function(data) {
                if (!data || data.success === false) {
                    showMessage(state, state.errorLabel);
                    return;
                }

                renderResults(state, data.results || []);
            })
            .catch(function(error) {
                if (error.name !== 'AbortError') {
                    showMessage(state, state.errorLabel);
                }
            });
    }

    function renderResults(state, items) {
        state.items = items || [];
        state.activeIndex = -1;

        if (!state.items.length) {
            showMessage(state, state.emptyLabel);
            return;
        }

        state.items = sortItemsByGroup(state.items);
        state.results.innerHTML = renderGroupedResults(state.items, state);

        setActive(state, 0);
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
                    '<span>' + escapeHtml(groupLabel) + '</span>' +
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
                escapeHtml(state.clearRecentLabel) +
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
            '<a id="' + escapeHtml(item.id || '') + '" class="campusfr-command-result" href="' + escapeHtml(item.url || '#') + '" data-index="' + index + '" role="option" aria-selected="false">' +
                '<span class="campusfr-command-result-icon">' + escapeHtml(item.icon) + '</span>' +
                '<span class="campusfr-command-result-body">' +
                    '<span class="campusfr-command-result-title">' + escapeHtml(item.title) + renderBestMatch(isBestMatch, bestLabel) + '</span>' +
                    '<span class="campusfr-command-result-subtitle">' + escapeHtml(item.subtitle) + '</span>' +
                '</span>' +
                '<span class="campusfr-command-result-meta">' +
                    renderFavorite(item, state) + 
                    renderResultAction(item) +
                    renderResultShortcut(item) +
                '</span>' +
            '</a>';
    } 

    function renderFavorite(item, state) {
        var favorite = isFavorite(item);

        return '' +
            '<button class="campusfr-command-favorite' + (favorite ? ' is-favorite' : '') + '"' +
            ' data-url="' + escapeHtml(item.url || '') + '"' +
            ' type="button"' +
            ' title="' + escapeHtml(state.favoriteTitle) + '"' +
            ' aria-label="' + escapeHtml(state.favoriteTitle) + '"' +
            ' aria-pressed="' + (favorite ? 'true' : 'false') + '">' +
            (favorite ? '★' : '☆') +
            '</button>';
    }

    function renderBestMatch(isBestMatch, bestLabel) {
        if (!isBestMatch) {
            return '';
        }

        return '<span class="campusfr-command-best-match">' + escapeHtml(bestLabel) + '</span>';
    }

    function renderResultAction(item) {
        if (!item.actionLabel) {
            return '<span class="campusfr-command-result-type">' + escapeHtml(item.type) + '</span>';
        }

        return '<span class="campusfr-command-result-action">' + escapeHtml(item.actionLabel) + '</span>';
    }

    function renderResultShortcut(item) {
        if (!item.shortcut) {
            return '';
        }

        return '<span class="campusfr-command-result-shortcut">' + escapeHtml(item.shortcut) + '</span>';
    }

    function showMessage(state, message) {
        state.items = [];
        state.activeIndex = -1;
        state.results.innerHTML = '<div class="campusfr-command-empty">' + escapeHtml(message) + '</div>';
        state.input.removeAttribute('aria-activedescendant');
    }

    function readStorage(key) {
        try {
            var raw = window.localStorage.getItem(key);

            if (!raw) {
                return [];
            }

            var value = JSON.parse(raw);

            return Array.isArray(value) ? value : [];
        } catch (e) {
            return [];
        }
    }

    function writeStorage(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            // Ignore storage errors.
        }
    }

    function isValidStoredItem(item) {
        return !!(item && item.url && item.title);
    }

    function normalizeStoredItem(item, group) {
        return {
            icon: item.icon || '',
            type: item.type || '',
            title: item.title || '',
            subtitle: item.subtitle || '',
            url: item.url || '',
            score: 0,
            group: group,
            groupLabel: '',
            shortcut: item.shortcut || '',
            actionLabel: item.actionLabel || ''
        };
    }

    function cleanStoredItems(items, limit, group) {
        var seen = {};
        var cleaned = [];

        items.forEach(function(item) {
            var normalized = normalizeStoredItem(item, group);

            if (!isValidStoredItem(normalized)) {
                return;
            }

            if (seen[normalized.url]) {
                return;
            }

            seen[normalized.url] = true;
            cleaned.push(normalized);
        });

        return cleaned.slice(0, limit);
    }

    function cleanupStorage() {
        writeStorage(
            STORAGE_KEYS.recent,
            cleanStoredItems(readStorage(STORAGE_KEYS.recent), STORAGE_LIMITS.recent, 'recent')
        );

        writeStorage(
            STORAGE_KEYS.favorites,
            cleanStoredItems(readStorage(STORAGE_KEYS.favorites), STORAGE_LIMITS.favorites, 'favorites')
        );
    }

    function rememberRecent(item) {
        if (!isValidStoredItem(item)) {
            return;
        }

        var recent = readStorage(STORAGE_KEYS.recent);
        var normalized = normalizeStoredItem(item, 'recent');

        recent = recent.filter(function(entry) {
            return entry.url !== normalized.url;
        });

        recent.unshift(normalized);
        recent = cleanStoredItems(recent, STORAGE_LIMITS.recent, 'recent');

        writeStorage(STORAGE_KEYS.recent, recent);
    }

    function isFavorite(item) {
        if (!item || !item.url) {
            return false;
        }

        return cleanStoredItems(
            readStorage(STORAGE_KEYS.favorites),
            STORAGE_LIMITS.favorites,
            'favorites'
        ).some(function(favorite) {
            return favorite.url === item.url;
        });
    }

    function toggleFavorite(item) {
        if (!isValidStoredItem(item)) {
            return;
        }

        var favorites = readStorage(STORAGE_KEYS.favorites);
        var normalized = normalizeStoredItem(item, 'favorites');

        var index = favorites.findIndex(function(f) {
            return f.url === normalized.url;
        });

        if (index >= 0) {
            favorites.splice(index, 1);
        } else {
            favorites.unshift(normalized);
        }

        favorites = cleanStoredItems(favorites, STORAGE_LIMITS.favorites, 'favorites');

        writeStorage(STORAGE_KEYS.favorites, favorites);
    }

    function getItemIndexFromRow(row) {
        if (!row) {
            return -1;
        }

        var index = parseInt(row.getAttribute('data-index'), 10);

        return isNaN(index) ? -1 : index;
    }

    function getActiveItem(state) {
        if (state.activeIndex < 0) {
            return null;
        }

        return state.items[state.activeIndex] || null;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    return {
        init: init
    };
});