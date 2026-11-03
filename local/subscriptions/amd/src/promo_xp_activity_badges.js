/**
 * Learner-only, read-only badges in Moodle 5 course activity cards / activity header.
 * Values are prepared by a server-side service from the current learner's XP ledger.
 */
export const init = (badges) => {
    if (!Array.isArray(badges) || !badges.length) {
        return;
    }

    const makeBadge = (item) => {
        const badge = document.createElement('span');
        badge.className = 'commerce-promo-xp-activity-badge commerce-promo-xp-activity-badge--' + item.state;
        badge.dataset.commercePromoXpBadge = String(item.cmid);
        badge.textContent = item.label;
        badge.title = item.title;
        badge.setAttribute('aria-label', item.title);
        return badge;
    };

    const findCard = (cmid) => {
        const id = String(cmid);
        // Moodle course formats differ: prefer the actual course module list item.
        return document.getElementById('module-' + id) ||
            document.querySelector('.activity[data-cmid="' + id + '"]') ||
            document.querySelector('.activity[data-id="' + id + '"]') ||
            document.querySelector('[data-for="cmitem"][data-id="' + id + '"]');
    };

    const render = () => {
        const coursePage = document.body.classList.contains('path-course') ||
            document.body.id.startsWith('page-course-');
        for (const item of badges) {
            if (!Number.isInteger(item.cmid) || !['earned', 'retry', 'potential'].includes(item.state)) {
                continue;
            }
            if (document.querySelector('[data-commerce-promo-xp-badge="' + item.cmid + '"]')) {
                continue;
            }
            const target = findCard(item.cmid);
            if (target && coursePage) {
                const anchor = target.querySelector('.activityname, .activity-name-area, .activityname > a') ||
                    target.querySelector('.activity-item') || target;
                anchor.appendChild(makeBadge(item));
                continue;
            }
            // A module view has one Moodle context, supplied by body data attribute
            // on versions where present, or by the module's URL query parameter.
            if (!coursePage) {
                const url = new URL(window.location.href);
                if (Number(url.searchParams.get('id')) !== item.cmid) {
                    continue;
                }
                const header = document.querySelector('.activity-header') ||
                    document.querySelector('#page-header .page-header-headings') ||
                    document.querySelector('#region-main .page-header-headings');
                if (header) {
                    header.appendChild(makeBadge(item));
                }
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', render, {once: true});
    } else {
        render();
    }
    // Moodle formats may asynchronously add activity cards when sections expand.
    const container = document.getElementById('page-content') || document.getElementById('region-main');
    if (container && typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(render);
        observer.observe(container, {childList: true, subtree: true});
    }
};
