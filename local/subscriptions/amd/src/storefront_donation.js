/* This file is part of Moodle - http://moodle.org/ */
/* eslint-disable no-undef */
define([], function() {
    const ROOT = '[data-storefront-donation]';

    const currencyDigits = (currency, fallback) => {
        const parsed = Number.parseInt(String(fallback ?? ''), 10);
        if (Number.isFinite(parsed) && parsed >= 0 && parsed <= 4) {
            return parsed;
        }
        try {
            return new Intl.NumberFormat(document.documentElement.lang || 'fr-FR', {
                style: 'currency',
                currency: String(currency || 'EUR').toUpperCase(),
            }).resolvedOptions().maximumFractionDigits;
        } catch (error) {
            return 2;
        }
    };

    const formatMinor = (minor, currency, digits) => {
        const code = String(currency || 'EUR').toUpperCase();
        const divisor = Math.pow(10, digits);
        const number = new Intl.NumberFormat(document.documentElement.lang || 'fr-FR', {
            style: 'decimal',
            minimumFractionDigits: digits,
            maximumFractionDigits: digits,
            useGrouping: true,
        }).format(Number(minor || 0) / divisor);
        return `${number}\u00a0${code}`;
    };

    const persistIntent = (root, units) => {
        const endpoint = root.dataset.donationIntentEndpoint || '';
        const sku = root.dataset.donationSku || '';
        if (!endpoint || !sku || typeof M === 'undefined' || !M.cfg || !M.cfg.sesskey) {
            return;
        }
        const body = new URLSearchParams({
            sesskey: M.cfg.sesskey,
            sku: sku,
            units: String(units),
        });
        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: body.toString(),
            keepalive: true,
        }).catch(() => {
            // The cart action re-persists the same intent authoritatively on submit.
        });
    };

    const refresh = (root, persist = false) => {
        const max = Math.max(1, Number.parseInt(root.dataset.donationMaxUnits || '1', 10));
        const fallback = Math.max(1, Number.parseInt(root.dataset.donationDefaultUnits || '1', 10));
        let units = Number.parseInt(root.dataset.donationUnits || String(fallback), 10);
        if (!Number.isFinite(units)) {
            units = fallback;
        }
        units = Math.max(1, Math.min(max, units));
        root.dataset.donationUnits = String(units);

        const unitMinor = Number.parseInt(root.dataset.donationUnitMinor || '0', 10);
        const currency = String(root.dataset.donationCurrency || 'EUR').toUpperCase();
        const digits = currencyDigits(currency, root.dataset.donationCurrencyDigits);
        const totalMinor = unitMinor * units;
        const total = root.querySelector('[data-donation-total]');
        if (total) {
            total.textContent = formatMinor(totalMinor, currency, digits);
        }

        const scope = root.closest('[data-storefront-donation-scope]') || root.parentElement;
        if (scope) {
            scope.querySelectorAll('[data-storefront-donation-units]').forEach((input) => {
                input.value = String(units);
            });
            scope.querySelectorAll('.commerce-storefront-price__values strong').forEach((price) => {
                price.textContent = formatMinor(totalMinor, currency, digits);
            });
        }

        root.querySelectorAll('[data-donation-preset]').forEach((button) => {
            const preset = Number.parseInt(button.dataset.donationPreset || '0', 10);
            button.textContent = formatMinor(unitMinor * preset, currency, digits);
            button.classList.toggle('is-active', preset === units);
        });

        const input = root.querySelector('[data-donation-amount-input]');
        if (input && unitMinor > 0) {
            const divisor = Math.pow(10, digits);
            const unitMajor = unitMinor / divisor;
            const selectedMajor = totalMinor / divisor;
            input.min = String(unitMajor);
            input.max = String(unitMajor * max);
            input.step = String(unitMajor);
            input.value = digits === 0 ? String(selectedMajor) : selectedMajor.toFixed(digits);
        }

        if (persist) {
            persistIntent(root, units);
        }
    };

    const bind = (root) => {
        if (root.dataset.storefrontDonationBound === '1') {
            refresh(root);
            return;
        }
        root.dataset.storefrontDonationBound = '1';
        root.dataset.donationUnits = root.dataset.donationDefaultUnits || '1';

        root.querySelectorAll('[data-donation-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const current = Number.parseInt(root.dataset.donationUnits || '1', 10);
                const delta = Number.parseInt(button.dataset.donationStep || '0', 10);
                root.dataset.donationUnits = String(current + delta);
                refresh(root, true);
            });
        });

        root.querySelectorAll('[data-donation-preset]').forEach((button) => {
            button.addEventListener('click', () => {
                root.dataset.donationUnits = button.dataset.donationPreset || '1';
                refresh(root, true);
            });
        });

        const input = root.querySelector('[data-donation-amount-input]');
        if (input) {
            input.addEventListener('change', () => {
                const currency = String(root.dataset.donationCurrency || 'EUR').toUpperCase();
                const digits = currencyDigits(currency, root.dataset.donationCurrencyDigits);
                const unitMinor = Number.parseInt(root.dataset.donationUnitMinor || '0', 10);
                const unitMajor = unitMinor / Math.pow(10, digits);
                const requested = Number.parseFloat(input.value || '0');
                if (unitMajor > 0 && Number.isFinite(requested)) {
                    root.dataset.donationUnits = String(Math.max(1, Math.round(requested / unitMajor)));
                }
                refresh(root, true);
            });
        }

        refresh(root);
    };

    const init = () => {
        document.querySelectorAll(ROOT).forEach(bind);
    };

    return {init};
});
