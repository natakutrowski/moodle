/* eslint-env amd */
/**
 * Dashboard revenue currency selector.
 *
 * @module local_subscriptions/dashboard_currency
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

const SELECTOR = {
    card: '[data-region="dashboard-revenue-card"]',
    currency: '[data-region="dashboard-revenue-currency"]',
    total: '[data-region="dashboard-revenue-total"]',
    subscriptions:
        '[data-region="dashboard-revenue-subscriptions"]',
    digital:
        '[data-region="dashboard-revenue-digital"]',
};

/**
 * Convert a currency code to a data-attribute suffix.
 *
 * @param {string} currency
 * @returns {string}
 */
const suffix = (currency) => {
    return String(currency || '')
        .trim()
        .toLowerCase();
};

/**
 * Update one card from its preloaded data attributes.
 *
 * @param {HTMLElement} card
 * @param {string} currency
 */
const updateCard = (card, currency) => {
    const key = suffix(currency);

    const total = card.getAttribute(`data-total-${key}`);
    const subscriptions = card.getAttribute(
        `data-subscriptions-${key}`
    );
    const digital = card.getAttribute(
        `data-digital-${key}`
    );

    if (total === null) {
        return;
    }

    const totalNode = card.querySelector(SELECTOR.total);
    const subscriptionsNode = card.querySelector(
        SELECTOR.subscriptions
    );
    const digitalNode = card.querySelector(
        SELECTOR.digital
    );

    if (totalNode) {
        totalNode.textContent = total;
    }

    if (subscriptionsNode && subscriptions !== null) {
        subscriptionsNode.textContent = subscriptions;
    }

    if (digitalNode && digital !== null) {
        digitalNode.textContent = digital;
    }
};

/**
 * Persist the user preference.
 *
 * @param {string} currency
 * @returns {Promise}
 */
const savePreference = (currency) => {
    return Ajax.call([{
        methodname:
            'local_subscriptions_save_dashboard_currency',
        args: {
            currency: currency,
        },
    }])[0];
};

/**
 * Bind a Dashboard revenue card.
 *
 * @param {HTMLElement} card
 */
const bindCard = (card) => {
    const select = card.querySelector(SELECTOR.currency);

    if (!select || select.dataset.initialized === '1') {
        return;
    }

    select.dataset.initialized = '1';

    select.addEventListener('change', async() => {
        const currency = select.value;

        updateCard(card, currency);

        try {
            await savePreference(currency);
        } catch (error) {
            Notification.exception(error);
        }
    });
};

/**
 * Initialize revenue selectors.
 */
export const init = () => {
    document.querySelectorAll(SELECTOR.card)
        .forEach(bindCard);
};