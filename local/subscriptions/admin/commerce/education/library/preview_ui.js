/* CampusFR D2.4.2c - CRM-only private flipbook UX. No network calls or media permissions. */
(function () {
    'use strict';
    function init() {
        const shell = document.querySelector('[data-cfr-preview-shell]');
        if (!shell) return;
        const toggle = shell.querySelector('[data-cfr-immersive-toggle]');
        if (!toggle) return;
        const openLabel = toggle.dataset.cfrOpen || toggle.textContent;
        const closeLabel = toggle.dataset.cfrClose || openLabel;
        let fallback = false;
        const sync = () => {
            const active = document.fullscreenElement === shell || fallback;
            toggle.textContent = active ? closeLabel : openLabel;
            toggle.setAttribute('aria-pressed', String(active));
            shell.classList.toggle('is-immersive', fallback);
            document.body.classList.toggle('cfr-d242c-overlay-open', fallback);
            // PageFlip uses responsive sizing. Ask it to recalculate after mode changes.
            window.dispatchEvent(new Event('resize'));
        };
        const leave = () => {
            if (document.fullscreenElement === shell) {
                const op = document.exitFullscreen();
                if (op && typeof op.catch === 'function') op.catch(() => {});
            } else {
                fallback = false;
                sync();
            }
        };
        toggle.addEventListener('click', () => {
            if (document.fullscreenElement === shell || fallback) {
                leave();
                return;
            }
            if (typeof shell.requestFullscreen === 'function') {
                try {
                    const op = shell.requestFullscreen();
                    if (op && typeof op.catch === 'function') {
                        op.catch(() => { fallback = true; sync(); });
                    }
                } catch (e) { fallback = true; sync(); }
            } else {
                fallback = true;
                sync();
            }
        });
        document.addEventListener('fullscreenchange', sync);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && fallback) { fallback = false; sync(); }
        });
        window.addEventListener('pagehide', () => {
            fallback = false;
            document.body.classList.remove('cfr-d242c-overlay-open');
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, {once: true});
    } else { init(); }
}());
