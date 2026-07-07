/* eslint-env amd */
define([], function() {
    'use strict';

    var STORAGE_KEYS = {
        recent: 'campusfr_command_center_recent',
        favorites: 'campusfr_command_center_favorites'
    };

    var STORAGE_LIMITS = {
        recent: 10,
        favorites: 25
    };

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

        var index = favorites.findIndex(function(favorite) {
            return favorite.url === normalized.url;
        });

        if (index >= 0) {
            favorites.splice(index, 1);
        } else {
            favorites.unshift(normalized);
        }

        favorites = cleanStoredItems(favorites, STORAGE_LIMITS.favorites, 'favorites');

        writeStorage(STORAGE_KEYS.favorites, favorites);
    }

    return {
        keys: STORAGE_KEYS,
        limits: STORAGE_LIMITS,
        read: readStorage,
        write: writeStorage,
        clean: cleanStoredItems,
        cleanup: cleanupStorage,
        rememberRecent: rememberRecent,
        isFavorite: isFavorite,
        toggleFavorite: toggleFavorite
    };
});