/* CampusFR C2.2a.2 — first-discovery surprise, replay restricted to administrators.
 * Independent cardboard flaps, persistent local open states.
 * Security: only links emitted by the Moodle PHP authorization gate are used.
 * The saved state is cosmetic; on every load, unauthorized doors are ignored.
 */
define([], function() {
    'use strict';

    const init = () => {
        const root = document.querySelector('.cfr-library[data-cfr-library-mode="advent"]');
        if (!root || root.dataset.cfrMagicReady === '1') return;
        root.dataset.cfrMagicReady = '1';
        // Server-supplied translations; JS is cosmetic and grants no access.
        const i18n = {
            opened: root.dataset.cfrOpened || 'Opened',
            closed: root.dataset.cfrClosed || 'Closed',
            replaywait: root.dataset.cfrReplaywait || 'Opening…',
            replaydone: root.dataset.cfrReplaydone || 'Animation played for door {day}.',
        };
        const countdown = root.querySelector('[data-cfr-countdown]');
        const nextunlock = Number.parseInt(root.dataset.cfrNextUnlock || '0', 10) * 1000;
        // Anchor the countdown to the server clock, not the visitor's possibly wrong wall clock.
        const serverNow = Number.parseInt(root.dataset.cfrServerNow || '0', 10) * 1000;
        const monotonicStart = performance.now();
        if (countdown && Number.isSafeInteger(nextunlock) && nextunlock > 0 &&
                Number.isSafeInteger(serverNow) && serverNow > 0) {
            const updateCountdown = () => {
                const elapsed = performance.now() - monotonicStart;
                const remaining = Math.max(0, Math.ceil((nextunlock - serverNow - elapsed) / 1000));
                if (!remaining) {
                    // Authoritative server re-check at the exact release boundary.
                    window.location.reload();
                    return;
                }
                const days = Math.floor(remaining / 86400);
                const hours = Math.floor((remaining % 86400) / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;
                const values = {days, hours, minutes, seconds};
                Object.keys(values).forEach(unit => {
                    const node = countdown.querySelector(`[data-cfr-countdown-${unit}]`);
                    if (node) node.textContent = unit === 'days' ? String(values[unit]) :
                        String(values[unit]).padStart(2, '0');
                });
            };
            updateCountdown();
            window.setInterval(updateCountdown, 1000);
        }
        const dialog = root.querySelector('[data-cfr-magic-dialog]');
        if (!dialog) return;
        const modalTitle = dialog.querySelector('[data-cfr-magic-title]');
        const modalDate = dialog.querySelector('[data-cfr-magic-date]');
        const modalLink = dialog.querySelector('[data-cfr-magic-link]');
        const closeModalButton = dialog.querySelector('[data-cfr-magic-close]');
        const foldModalButton = dialog.querySelector('[data-cfr-magic-fold]');
        // Collection + Moodle account are already part of this server-supplied prefix.
        const storagePrefix = root.dataset.cfrSessionKey || '';
        const storageKey = storagePrefix ? storagePrefix + '.doors.v2' : '';
        const seenKey = storagePrefix ? storagePrefix + '.first-surprises.v1' : '';
        const reducedMotion = !!(window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        const swingMs = reducedMotion ? 0 : 870;
        const timers = new Map();
        const available = new Map();
        let currentModal = null;
        let focusReturn = null;
        let storedDays = [];
        const seenDays = new Set();
        const adminReplay = root.querySelector('[data-cfr-admin-replay]');
        const replayButton = adminReplay && adminReplay.querySelector('[data-cfr-replay-first]');
        const replaySelect = adminReplay && adminReplay.querySelector('[data-cfr-replay-day]');
        const replayStatus = adminReplay && adminReplay.querySelector('[data-cfr-replay-status]');
        let replayTimer = null;

        const readStorage = key => {
            if (!key) return [];
            try {
                const value = JSON.parse(window.localStorage.getItem(key) || '[]');
                return Array.isArray(value) ? value.filter(Number.isSafeInteger) : [];
            } catch (_) {
                return [];
            }
        };
        storedDays = readStorage(storageKey);
        readStorage(seenKey).forEach(day => seenDays.add(day));
        const markSeen = slot => {
            const day = Number.parseInt(slot.dataset.cfrDay || '', 10);
            if (!Number.isSafeInteger(day)) return;
            seenDays.add(day);
            if (!seenKey) return;
            try { window.localStorage.setItem(seenKey, JSON.stringify([...seenDays].sort((a, b) => a - b))); }
            catch (_) { /* Optional cosmetic memory only. */ }
        };
        const persist = () => {
            if (!storageKey) return;
            try {
                const days = [...available.entries()]
                    .filter(([, slot]) => slot.classList.contains('is-opened') ||
                        slot.classList.contains('is-opening'))
                    .map(([day]) => day).sort((a, b) => a - b);
                window.localStorage.setItem(storageKey, JSON.stringify(days));
            } catch (_) { /* Storage is optional; the book remains functional. */ }
        };
        const validDoor = slot => slot &&
            !!slot.querySelector('[data-cfr-door][href]') &&
            !!slot.querySelector('.cfr-advent__reveal .cfr-advent__read-link[href]');
        const panelFor = slot => slot.querySelector('.cfr-advent__reveal');
        const doorFor = slot => slot.querySelector('[data-cfr-door][href]');
        const statusFor = slot => slot.querySelector('.cfr-advent__status');
        const cancelTimer = slot => {
            if (timers.has(slot)) {
                window.clearTimeout(timers.get(slot));
                timers.delete(slot);
            }
        };
        const setDoorState = (slot, opened) => {
            const door = doorFor(slot);
            if (door) {
                door.setAttribute('aria-expanded', opened ? 'true' : 'false');
                door.setAttribute('aria-hidden', opened ? 'true' : 'false');
                door.tabIndex = opened ? -1 : 0;
            }
        };
        const openSlot = (slot, restore = false, forceSurprise = false) => {
            if (!validDoor(slot)) return;
            cancelTimer(slot);
            const panel = panelFor(slot);
            if (!panel) return;
            panel.hidden = false;
            slot.classList.remove('is-closing');
            slot.classList.toggle('is-opening', !restore);
            slot.classList.toggle('is-opened', restore);
            slot.classList.toggle('is-restored', restore);
            setDoorState(slot, true);
            const status = statusFor(slot);
            if (status) status.textContent = i18n.opened;
            if (!restore) {
                const trigger = panel.querySelector('[data-cfr-discover]');
                const day = Number.parseInt(slot.dataset.cfrDay || '', 10);
                const firstDiscovery = forceSurprise || !seenDays.has(day);
                timers.set(slot, window.setTimeout(() => {
                    timers.delete(slot);
                    // A door folded before its animation ends must not reveal the surprise.
                    if (!slot.classList.contains('is-opening')) return;
                    slot.classList.remove('is-opening');
                    slot.classList.add('is-opened');
                    persist();
                    if (firstDiscovery) showSurprise(slot, trigger);
                    else if (trigger) trigger.focus({preventScroll: true});
                }, swingMs + (reducedMotion ? 0 : 100)));
            }
            persist();
        };
        const closeModal = () => {
            if (dialog.open && typeof dialog.close === 'function') dialog.close();
            else if (dialog.hasAttribute('open')) dialog.removeAttribute('open');
        };
        const foldSlot = (slot, focusDoor = true) => {
            if (!validDoor(slot)) return;
            cancelTimer(slot);
            if (currentModal === slot) closeModal();
            slot.classList.remove('is-opening', 'is-opened', 'is-restored');
            slot.classList.add('is-closing');
            setDoorState(slot, false);
            const status = statusFor(slot);
            if (status) status.textContent = i18n.closed;
            persist();
            timers.set(slot, window.setTimeout(() => {
                timers.delete(slot);
                // A quick re-open must not be hidden by a stale closing timer.
                if (!slot.classList.contains('is-closing')) return;
                slot.classList.remove('is-closing');
                const panel = panelFor(slot);
                if (panel) panel.hidden = true;
            }, swingMs + 30));
            const door = doorFor(slot);
            if (focusDoor && door) door.focus({preventScroll: true});
        };
        const showSurprise = (slot, trigger) => {
            if (!validDoor(slot) || !slot.classList.contains('is-opened') &&
                !slot.classList.contains('is-opening')) return;
            const panel = panelFor(slot);
            const link = panel.querySelector('.cfr-advent__read-link[href]');
            const bookTitle = panel.querySelector('strong');
            const day = slot.querySelector('.cfr-advent__date');
            if (!link || !bookTitle || !day || !modalTitle || !modalLink || !modalDate) return;
            modalTitle.textContent = bookTitle.textContent;
            modalDate.textContent = day.textContent;
            modalLink.href = link.href;
            currentModal = slot;
            focusReturn = trigger || panel.querySelector('[data-cfr-discover]');
            if (!dialog.open) {
                if (typeof dialog.showModal === 'function') dialog.showModal();
                else dialog.setAttribute('open', '');
            }
            markSeen(slot);
            if (closeModalButton) closeModalButton.focus({preventScroll: true});
        };
        const onDialogClosed = () => {
            // Closing the large card never closes a little cardboard door.
            currentModal = null;
            if (focusReturn && focusReturn.isConnected) focusReturn.focus({preventScroll: true});
            focusReturn = null;
        };

        root.querySelectorAll('.cfr-advent__slot.is-available[data-cfr-day]').forEach(slot => {
            if (!validDoor(slot)) return;
            const day = Number.parseInt(slot.dataset.cfrDay || '', 10);
            if (!Number.isSafeInteger(day)) return;
            available.set(day, slot);
            const door = doorFor(slot);
            const panel = panelFor(slot);
            const discover = panel.querySelector('[data-cfr-discover]');
            const fold = panel.querySelector('[data-cfr-door-close]');
            if (storedDays.includes(day)) openSlot(slot, true);
            door.addEventListener('click', event => {
                // Modified clicks and disabled JavaScript keep the genuine authorized URL.
                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.altKey || event.shiftKey) return;
                event.preventDefault();
                openSlot(slot);
            });
            if (discover) discover.addEventListener('click', () => showSurprise(slot, discover));
            if (fold) fold.addEventListener('click', () => foldSlot(slot));
        });
        // Admin-only opt-in preview control, rendered by PHP only for authorized books.
        // It does not clear discovery history and never grants access to a locked slot.
        if (replayButton && replaySelect) {
            replayButton.addEventListener('click', () => {
                const day = Number.parseInt(replaySelect.value || '', 10);
                const slot = available.get(day);
                if (!slot || !validDoor(slot) || replayTimer !== null) return;
                replayButton.disabled = true;
                if (replayStatus) replayStatus.textContent = i18n.replaywait;
                focusReturn = null;
                closeModal();
                const wasOpen = slot.classList.contains('is-opening') ||
                    slot.classList.contains('is-opened');
                if (wasOpen) foldSlot(slot, false);
                const delay = wasOpen ? swingMs + (reducedMotion ? 0 : 110) : 0;
                replayTimer = window.setTimeout(() => {
                    replayTimer = null;
                    if (validDoor(slot)) openSlot(slot, false, true);
                    replayButton.disabled = false;
                    if (replayStatus) replayStatus.textContent = i18n.replaydone.replace('{day}', String(day));
                }, delay);
            });
        }
        // Prune stale doors no longer authorized; discovery history has no effect on access.
        persist();
        if (closeModalButton) closeModalButton.addEventListener('click', closeModal);
        if (foldModalButton) foldModalButton.addEventListener('click', () => {
            const slot = currentModal;
            focusReturn = null; // The preview trigger is about to become hidden.
            closeModal();
            if (slot) foldSlot(slot, true);
        });
        dialog.addEventListener('close', onDialogClosed);
        dialog.addEventListener('click', event => {
            if (event.target === dialog) closeModal();
        });
        // Escape is supported natively by showModal(); fallback for older browsers.
        dialog.addEventListener('keydown', event => {
            if (event.key === 'Escape' && typeof dialog.showModal !== 'function') {
                event.preventDefault();
                closeModal();
                onDialogClosed();
            }
        });
    };
    return {init};
});
