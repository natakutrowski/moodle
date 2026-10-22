/**
 * Adds a compact resume indicator to the CampusFR cart control when a durable
 * Direct Purchase exists in the current browser session. This indicator is a
 * dot, deliberately distinct from the cart's own numeric item-count badge.
 *
 * The public CampusFR/Edly navbar may be inserted after this AMD module runs.
 * Both the public Boutique link and the public cart are eligible to show the
 * resume dot. They are identified by their PUBLIC ROUTES (including FR/EN/RU
 * aliases), not by a generic cart icon. Commerce / Sales admin navigation and
 * other content blocks reuse the same icons and MUST stay undecorated.
 *
 * @module local_subscriptions/direct_purchase_nav_badge
 */

const CART_PATH = '/local/subscriptions/cart.php';
const LEGACY_BOUTIQUE_PATH = '/local/subscriptions/digital_catalog.php';
// Canonical public routes are defined in subscription_config::PUBLIC_ROUTES.
// Do not expand this list to admin commerce/storefront URLs.
const PUBLIC_SHOP_PATHS = ['/boutique', '/shop', '/magazin'];
const PUBLIC_CART_PATHS = ['/panier', '/cart', '/korzina'];
const NAVIGATION_SELECTOR = [
    'header',
    'nav',
    '.navbar',
    '.navbar-area',
    '.main-navbar',
    '[role="navigation"]',
].join(', ');
// Cart controls without a known public href must opt in explicitly.
// Never identify either Boutique or Cart from a generic Font Awesome icon.
const EXPLICIT_CART_SELECTOR = [
    'a[data-region="cart"]',
    'button[data-region="cart"]',
    'a[data-action="cart"]',
    'button[data-action="cart"]',
].join(', ');
const BADGE_SELECTOR = '[data-direct-purchase-nav-badge]';
const OBSERVE_FOR_MS = 15000;

const isPurchaseNavigationRoute = element => {
    if (!(element instanceof HTMLAnchorElement) || !element.href) {
        return false;
    }

    try {
        const url = new URL(element.href, window.location.href);
        if (url.origin !== window.location.origin) {
            return false;
        }
        // Some Moodle installations have a site prefix; avoid matching
        // an accidental /admin/shop or /crm/cart URL under that prefix.
        const path = url.pathname.replace(/\/+$/, '') || '/';
        if (/(?:^|\/)admin(?:\/|$)/.test(path) || /(?:^|\/)crm(?:\/|$)/.test(path)) {
            return false;
        }
        const paths = [CART_PATH, LEGACY_BOUTIQUE_PATH, ...PUBLIC_SHOP_PATHS, ...PUBLIC_CART_PATHS];
        return paths.some(route => path === route || path.endsWith(route));
    } catch (error) {
        return false;
    }
};

const isVisible = element => {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    return element.getClientRects().length > 0;
};

const collectTargets = () => {
    const targets = new Set();

    document.querySelectorAll(NAVIGATION_SELECTOR).forEach(scope => {
        scope.querySelectorAll('a[href]').forEach(link => {
            if (isPurchaseNavigationRoute(link)) {
                targets.add(link);
            }
        });

        // A known cart control may be a button rather than a link.
        // Explicit data attributes are allowed, but icon-only matches are not.
        scope.querySelectorAll(EXPLICIT_CART_SELECTOR).forEach(control => {
            targets.add(control);
        });
    });

    return Array.from(targets).filter(isVisible);
};

const decorate = (control, config) => {
    if (control.querySelector(BADGE_SELECTOR)) {
        return;
    }

    const computed = window.getComputedStyle(control);
    if (computed.position === 'static') {
        control.style.position = 'relative';
    }
    control.style.overflow = 'visible';

    const badge = document.createElement('span');
    badge.dataset.directPurchaseNavBadge = '1';
    badge.setAttribute('role', 'status');
    badge.setAttribute('aria-label', String(config.label || ''));
    badge.title = String(config.label || '');
    badge.textContent = '';

    Object.assign(badge.style, {
        position: 'absolute',
        right: '1px',
        bottom: '1px',
        zIndex: '20',
        width: '10px',
        height: '10px',
        border: '2px solid #fff',
        borderRadius: '50%',
        background: '#f72585',
        boxSizing: 'border-box',
        pointerEvents: 'none',
        transform: 'translate(35%, 35%)',
        boxShadow: '0 2px 7px rgba(52, 34, 95, .22)',
    });

    control.append(badge);
};

const decorateAvailableTargets = config => {
    const targets = collectTargets();
    targets.forEach(control => decorate(control, config));
    return targets.length > 0;
};

export const init = config => {
    const effectiveConfig = config || {};
    let observer = null;
    let scheduled = false;

    const run = () => {
        scheduled = false;
        decorateAvailableTargets(effectiveConfig);
    };

    const schedule = () => {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.requestAnimationFrame(run);
    };

    const startObserver = () => {
        if (!document.documentElement || observer) {
            return;
        }

        observer = new MutationObserver(schedule);
        observer.observe(document.documentElement, {
            childList: true,
            subtree: true,
        });

        window.setTimeout(() => {
            if (observer) {
                observer.disconnect();
                observer = null;
            }
        }, OBSERVE_FOR_MS);
    };

    const start = () => {
        run();
        startObserver();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, {once: true});
    } else {
        start();
    }
};
