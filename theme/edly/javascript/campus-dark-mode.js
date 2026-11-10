/* CampusFR Edly dark mode: progressive enhancement, no frontend framework. */
(function () {
    'use strict';
    var root = document.documentElement;
    var guestKey = 'campusfr.edly.darkmode';
    var pending = false;
    var hintClosed = false;

    function modeIsDark() {
        return root.classList.contains('campus-dark');
    }

    function refreshButtons() {
        var dark = modeIsDark();
        document.querySelectorAll('[data-campus-dark-toggle]').forEach(function (button) {
            var label = dark ? button.dataset.campusDarkLabelOff : button.dataset.campusDarkLabelOn;
            button.setAttribute('aria-pressed', String(dark));
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.disabled = pending;
        });
    }

    function setMode(dark) {
        root.classList.toggle('campus-dark', dark);
        if (dark) {
            hideHint(document.querySelector('[data-campus-dark-hint]'));
        }
        refreshButtons();
    }

    // V24.1: first-party cookie fallback for browsers that clear/deny storage
    // during cart POST redirects. Keep the existing localStorage preference as
    // the primary source; never persist when the global feature is disabled.
    function guestCookieValue() {
        var name = guestKey + '=';
        var cookieText = '';
        try { cookieText = document.cookie || ''; } catch (ignored) { return null; }
        var parts = cookieText ? cookieText.split(';') : [];
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i].trim();
            if (part.indexOf(name) === 0) {
                return part.slice(name.length);
            }
        }
        return null;
    }

    // V24.2: tri-state read distinguishes an explicit guest choice from no
    // choice at all. This matters on the first login after account activation.
    function savedGuestChoice() {
        try {
            var stored = window.localStorage.getItem(guestKey);
            if (stored === 'dark' || stored === 'light') {
                return stored;
            }
        } catch (ignored) {}
        var cookie = guestCookieValue();
        return cookie === 'dark' || cookie === 'light' ? cookie : null;
    }

    function readGuest() {
        return savedGuestChoice() === 'dark';
    }

    function storeGuest(dark) {
        var value = dark ? 'dark' : 'light';
        try { window.localStorage.setItem(guestKey, value); } catch (ignored) {}
        try {
            document.cookie = guestKey + '=' + value + '; Max-Age=31536000; Path=/; SameSite=Lax' +
                (window.location.protocol === 'https:' ? '; Secure' : '');
        } catch (ignored) {}
    }

    function restoreGuest() {
        if (root.dataset.campusDarkAllowed !== '1' || root.dataset.campusDarkAuth === '1') {
            return;
        }
        setMode(readGuest());
    }

    function persistUser(button, dark) {
        var body = new URLSearchParams();
        body.set('mode', dark ? 'dark' : 'light');
        body.set('sesskey', button.dataset.campusDarkSesskey);
        return fetch(button.dataset.campusDarkUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept': 'application/json'},
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) { throw new Error('HTTP ' + response.status); }
            return response.json();
        }).then(function (data) {
            if (!data.success || data.mode !== (dark ? 'dark' : 'light')) {
                throw new Error('Unexpected theme response');
            }
        });
    }

    function importGuestChoiceForNewMember() {
        // Only the first signed-in page WITHOUT a Moodle dark-mode preference.
        // Never replace an existing user setting, even an explicit light one.
        // The global admin switch is authoritative on every route.
        if (root.dataset.campusDarkAllowed !== '1' ||
                root.dataset.campusDarkAuth !== '1' ||
                root.dataset.campusDarkPrefUnset !== '1') {
            return;
        }
        var choice = savedGuestChoice();
        if (choice === null) { return; }
        var button = document.querySelector('[data-campus-dark-toggle][data-campus-dark-url][data-campus-dark-sesskey]');
        if (!button || !button.dataset.campusDarkUrl || !button.dataset.campusDarkSesskey) {
            return; // Keep browser appearance, try again on the next eligible page.
        }
        var dark = choice === 'dark';
        setMode(dark);
        pending = true;
        refreshButtons();
        persistUser(button, dark).then(function () {
            // Prevent a second import in this page after successful persistence.
            root.dataset.campusDarkPrefUnset = '0';
        }).catch(function (error) {
            // Do not alter the guest choice or fake a saved Moodle preference.
            // A later eligible page can retry if the session/POST was interrupted.
            console.warn('CampusFR dark mode: first-login preference import failed:', error);
        }).finally(function () {
            pending = false;
            refreshButtons();
        });
    }

    function persistHintDismissal(hint) {
        if (!hint || !hint.dataset.campusDarkHintDismissUrl || !hint.dataset.campusDarkHintSesskey) {
            return Promise.resolve();
        }
        var body = new URLSearchParams();
        body.set('dismiss', '1');
        body.set('sesskey', hint.dataset.campusDarkHintSesskey);
        return fetch(hint.dataset.campusDarkHintDismissUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept': 'application/json'},
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) { throw new Error('HTTP ' + response.status); }
            return response.json();
        }).then(function (data) {
            if (!data.success) {
                throw new Error('Unexpected hint response');
            }
        });
    }

    function hideHint(hint) {
        if (!hint) { return; }
        hint.hidden = true;
        hint.classList.remove('campus-dark-hint--visible');
        document.querySelectorAll('.campus-dark-toggle--hinted').forEach(function (toggle) {
            toggle.classList.remove('campus-dark-toggle--hinted');
        });
        hintClosed = true;
    }

    function positionHint(hint, button) {
        if (!hint || !button || hint.hidden) { return; }
        var rect = button.getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 1280;
        var maxWidth = Math.min(360, Math.max(280, viewportWidth - 32));
        hint.style.width = maxWidth + 'px';
        var measuredWidth = hint.offsetWidth || maxWidth;
        var left = Math.max(16, Math.min(viewportWidth - measuredWidth - 16,
            rect.left + rect.width / 2 - measuredWidth / 2));
        var arrowLeft = Math.max(34, Math.min(measuredWidth - 34, rect.left + (rect.width / 2) - left));
        hint.style.left = Math.round(left) + 'px';
        hint.style.top = Math.round(rect.bottom + 14) + 'px';
        hint.style.setProperty('--campus-dark-hint-arrow-left', Math.round(arrowLeft) + 'px');
    }

    function initHint() {
        var hint = document.querySelector('[data-campus-dark-hint]');
        var isMobile = window.matchMedia && window.matchMedia('(max-width: 1199px)').matches;
        var button = isMobile ? document.querySelector('.campus-dark-toggle--mobile') :
            (document.querySelector('.campus-dark-toggle--desktop.campus-dark-toggle--positioned') ||
                document.querySelector('.campus-dark-toggle--desktop'));
        if (!hint || !button || hintClosed || modeIsDark()) {
            if (hint) { hint.hidden = true; }
            return;
        }
        // The desktop navbar collapses on mobile. Move the hint out of it so
        // a display:none ancestor cannot hide the fixed-position popup.
        if (hint.parentNode !== document.body) {
            document.body.appendChild(hint);
        }
        hint.hidden = false;
        hint.classList.add('campus-dark-hint--visible');
        button.classList.add('campus-dark-toggle--hinted');
        positionHint(hint, button);
        var close = hint.querySelector('[data-campus-dark-hint-close]');
        if (close && !close.dataset.bound) {
            close.dataset.bound = '1';
            close.addEventListener('click', function () {
                hideHint(hint);
            });
        }
        var tryButton = hint.querySelector('[data-campus-dark-hint-try]');
        if (tryButton && !tryButton.dataset.bound) {
            tryButton.dataset.bound = '1';
            tryButton.addEventListener('click', function () {
                hideHint(hint);
                var mobile = window.matchMedia && window.matchMedia('(max-width: 1199px)').matches;
                var active = mobile ? document.querySelector('.campus-dark-toggle--mobile') :
                    document.querySelector('.campus-dark-toggle--desktop');
                (active || button).click();
            });
        }
        var dismiss = hint.querySelector('[data-campus-dark-hint-dismiss]');
        if (dismiss && !dismiss.dataset.bound) {
            dismiss.dataset.bound = '1';
            dismiss.addEventListener('click', function () {
                dismiss.disabled = true;
                persistHintDismissal(hint).then(function () {
                    hideHint(hint);
                }).catch(function (error) {
                    console.warn('CampusFR dark-mode hint dismissal was not saved:', error);
                }).finally(function () {
                    dismiss.disabled = false;
                });
            });
        }
        window.addEventListener('resize', function () {
            var mobile = window.matchMedia && window.matchMedia('(max-width: 1199px)').matches;
            var target = mobile ? document.querySelector('.campus-dark-toggle--mobile') :
                document.querySelector('.campus-dark-toggle--desktop');
            if (target) {
                button.classList.remove('campus-dark-toggle--hinted');
                target.classList.add('campus-dark-toggle--hinted');
                positionHint(hint, target);
            }
        });
        window.addEventListener('scroll', function () {
            positionHint(hint, button);
        }, {passive: true});
    }

    function init() {
        // CRM/showroom routes are explicitly light. Defensive guard for SPA edges.
        if (root.dataset.campusDarkAllowed === '0') {
            root.classList.remove('campus-dark');
            return;
        }
        // Restore the guest choice after a cart redirect or BFCache return.
        restoreGuest();
        // Guest -> new Moodle account: sync only if that account has no choice.
        importGuestChoiceForNewMember();
        window.addEventListener('pageshow', restoreGuest);
        // The desktop button starts hidden to avoid a first-frame jump.
        // Signed-in members have the customer navbar; anonymous visitors and
        // Moodle Guest users have the public guest navbar instead. Position
        // the SAME button in the correct navbar without duplicating controls.
        if (typeof document.querySelector === 'function') {
            var desktop = document.querySelector('.campus-dark-toggle--desktop');
            var customerNav = document.querySelector('.main-navbar .campus-customer-nav');
            var campus = customerNav && customerNav.querySelector('.campus-customer-nav__home');
            var guestNav = document.querySelector('.main-navbar .campus-guest-nav');
            if (desktop && customerNav && campus) {
                customerNav.insertBefore(desktop, campus);
                desktop.classList.add('campus-dark-toggle--positioned');
            } else if (desktop && guestNav) {
                // First item, ahead of language / boutique / login links.
                guestNav.insertBefore(desktop, guestNav.firstChild);
                desktop.classList.add('campus-dark-toggle--positioned');
            } else if (desktop) {
                // Some public pages omit guest_navigation entirely. In that
                // case keep the switch in its original Edly navbar location.
                desktop.classList.add('campus-dark-toggle--positioned');
            }
        }
        initHint();
        // V27: Delegate one native click handler for both desktop and mobile.
        // Edly/Moodle may move or recreate navigation buttons after DOM ready;
        // a direct listener on the initial node would be lost. Capturing the
        // click also avoids competing navbar handlers swallowing a tap.
        document.addEventListener('click', function (event) {
            var target = event.target && (event.target.closest ? event.target : event.target.parentElement);
            var button = target && target.closest('[data-campus-dark-toggle]');
            if (!button || button.disabled || pending || root.dataset.campusDarkAllowed === '0') {
                return;
            }
            var previous = modeIsDark();
            var next = !previous;
            setMode(next);
            if (root.dataset.campusDarkAuth !== '1') {
                storeGuest(next);
                return;
            }
            pending = true;
            refreshButtons();
            persistUser(button, next).catch(function (error) {
                // Restore the server-backed state on failure (expired session etc.).
                setMode(previous);
                console.error('CampusFR dark-mode preference was not saved:', error);
            }).finally(function () {
                pending = false;
                refreshButtons();
            });
        }, true);
        refreshButtons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
