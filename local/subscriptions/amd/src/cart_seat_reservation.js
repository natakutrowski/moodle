// This file is part of Moodle - http://moodle.org/

/**
 * Countdown for temporary pedagogical seat holds in the cart.
 *
 * @module     local_subscriptions/cart_seat_reservation
 * @copyright  2026 CampusFR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTOR_SUMMARY = '[data-cart-seat-summary]';
const SELECTOR_COUNTDOWN = '[data-cart-seat-countdown]';

const formatRemaining = (seconds) => {
    const safe = Math.max(0, seconds);
    const minutes = Math.floor(safe / 60);
    const remainder = safe % 60;
    return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
};

export const init = () => {
    const summary = document.querySelector(SELECTOR_SUMMARY);
    if (!summary || summary.classList.contains('is-expired')) {
        return;
    }

    const countdown = summary.querySelector(SELECTOR_COUNTDOWN);
    const expiresAt = Number.parseInt(summary.dataset.expiresAt || '0', 10);
    if (!countdown || !Number.isFinite(expiresAt) || expiresAt <= 0) {
        return;
    }

    let timer = 0;
    const render = () => {
        const now = Math.floor(Date.now() / 1000);
        const remaining = Math.max(0, expiresAt - now);
        countdown.textContent = formatRemaining(remaining);

        if (remaining <= 0) {
            window.clearInterval(timer);
            window.location.reload();
        }
    };

    render();
    timer = window.setInterval(render, 1000);
};
