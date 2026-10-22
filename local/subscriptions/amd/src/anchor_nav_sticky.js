// This file is part of Moodle - http://moodle.org/

/**
 * A1-only contextual navigation: below the fixed Moodle topbar, after the Hero.
 *
 * @module     local_subscriptions/anchor_nav_sticky
 * @copyright  2026 CampusFR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* global define */
define([], function() {
    const NAV_SELECTOR = '[data-showroom-anchor-nav][data-showroom-after-hero-nav="1"]';
    const TOPBAR_SELECTOR = [
        // The A1 storefront uses this Edly header, not a nav.navbar.
        '.showroom-shell__topbar',
        '#page-wrapper > nav.navbar',
        '#page-wrapper .navbar',
        'nav.navbar',
        '[role="banner"]',
        '#page-wrapper > header',
        'nav.navbar.fixed-top',
        '.navbar.fixed-top',
        '.main-navbar',
        '.navbar-area',
        '.top-header-area',
        '.header-area',
        '.sticky-header',
        '.primary-navigation',
        '#header-area',
        'header.main-header',
        'header.site-header',
        '#header',
    ].join(', ');

    const topbarBottom = (showroom) => {
        // The actual CampusFR Showroom header is a <header>, rather than a
        // Bootstrap nav.navbar. It can visually cover a fixed navigation at
        // top: 0, even when that navigation has become visible.
        const realTopbar = document.querySelector('.showroom-shell__topbar');
        if (realTopbar) {
            const style = window.getComputedStyle(realTopbar);
            const bounds = realTopbar.getBoundingClientRect();
            // Measure the visible bottom edge, not an assumed header height.
            // If the header scrolls out of view, the navigation may use top: 0.
            if (style.display !== 'none' && style.visibility !== 'hidden'
                && bounds.height >= 20 && bounds.height <= 200
                && bounds.top <= 20 && bounds.bottom > 0) {
                return Math.round(Math.max(0, Math.min(200, bounds.bottom)));
            }
        }

        // Preserve legacy topbar detection outside the full-width Edly shell.
        let bottom = 0;
        document.querySelectorAll(TOPBAR_SELECTOR).forEach((element) => {
            if (element === realTopbar || showroom.contains(element)) {
                return;
            }
            const style = window.getComputedStyle(element);
            if (style.position !== 'fixed' && style.position !== 'sticky') {
                return;
            }
            if (style.display === 'none' || style.visibility === 'hidden') {
                return;
            }
            const bounds = element.getBoundingClientRect();
            if (bounds.height < 20 || bounds.height > 180 || bounds.top > 12 || bounds.bottom <= 0) {
                return;
            }
            bottom = Math.max(bottom, bounds.bottom);
        });
        return Math.round(Math.max(0, Math.min(180, bottom)));
    };

    const init = () => {
        const nav = document.querySelector(NAV_SELECTOR);
        const showroom = nav?.closest('.commerce-showroom[data-showroom="a1-course"]');
        const hero = showroom?.querySelector('#showroom-top');
        if (!nav || !showroom || !hero || nav.dataset.afterHeroBound === '1') {
            return;
        }
        nav.dataset.afterHeroBound = '1';
        showroom.classList.add('has-a1-after-hero-nav');

        // A fixed bar inside the Edly/Moodle content can be clipped or positioned
        // relative to a transformed ancestor. Keep the original node (and its
        // event listeners), but place it directly under body so it is fixed
        // against the actual viewport. The main showroom keeps its normal flow.
        document.body.appendChild(nav);

        let scheduled = false;
        const links = Array.from(nav.querySelectorAll('[data-showroom-section-anchor]'));
        const tracked = links.map((link) => {
            const targetId = (link.getAttribute('href') || '').slice(1);
            return {link, target: document.getElementById(targetId)};
        }).filter((item) => item.target);

        const update = () => {
            scheduled = false;
            const topbar = topbarBottom(showroom);
            nav.style.setProperty('--showroom-a1-topbar-bottom', `${topbar}px`);
            const clearance = Math.ceil(topbar + nav.getBoundingClientRect().height + 16);
            showroom.style.setProperty('--showroom-a1-anchor-clearance', `${clearance}px`);

            // A small tolerance avoids flicker exactly at the Hero boundary.
            const visible = hero.getBoundingClientRect().bottom <= topbar + 2;
            nav.classList.toggle('is-after-hero-visible', visible);
            nav.setAttribute('aria-hidden', visible ? 'false' : 'true');
            nav.inert = !visible;

            if (!visible) {
                tracked.forEach(({link}) => link.removeAttribute('aria-current'));
                return;
            }

            const threshold = topbar + nav.getBoundingClientRect().height + 25;
            let active = null;
            tracked.forEach(({link, target}) => {
                if (target.getBoundingClientRect().top <= threshold) {
                    active = link;
                }
            });
            tracked.forEach(({link}) => {
                if (link === active) {
                    link.setAttribute('aria-current', 'location');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };

        const schedule = () => {
            if (scheduled) {
                return;
            }
            scheduled = true;
            window.requestAnimationFrame(update);
        };

        window.addEventListener('scroll', schedule, {passive: true});
        // Also observe theme scroll containers, which do not always bubble to window.
        document.addEventListener('scroll', schedule, {passive: true, capture: true});
        window.addEventListener('resize', schedule, {passive: true});
        window.addEventListener('load', schedule);
        if (window.ResizeObserver) {
            const observer = new ResizeObserver(schedule);
            observer.observe(hero);
            observer.observe(nav);
        }
        schedule();
    };

    return {init};
});
