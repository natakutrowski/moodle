import {
    hidePaymentSplash,
    selectedPaymentMethod,
    showPaymentSplash,
} from './checkout_payment_splash';

// Commerce 7.96H1 — provider-agnostic checkout submission state.

const FORM = '[data-checkout-payment-form]';
const SUBMIT = '[data-checkout-submit]';

const hostedSplashMethods = form => {
    const raw = String(
        form?.dataset.hostedSplashMethods
        || ''
    ).trim();

    if (raw === '') {
        return [];
    }

    try {
        const values =
            JSON.parse(raw);

        return Array.isArray(values)
            ? values.map(
                value =>
                    String(value || '').toLowerCase()
            )
            : [];
    } catch (error) {
        return [];
    }
};

export const init = () => {
    document.querySelectorAll(FORM).forEach(form => {
        form.addEventListener('submit', event => {
            if (
                event.defaultPrevented
                || (
                    typeof form.checkValidity === 'function'
                    && !form.checkValidity()
                )
            ) {
                return;
            }

            const submit = form.querySelector(SUBMIT);
            if (!submit) {
                return;
            }

            const processing = String(
                submit.dataset.processingLabel || ''
            ).trim();

            submit.disabled = true;
            submit.setAttribute('aria-busy', 'true');

            if (processing !== '') {
                submit.textContent = processing;
            }

            const method =
                selectedPaymentMethod(
                    form
                );

            if (
                hostedSplashMethods(
                    form
                ).includes(
                    method
                )
            ) {
                showPaymentSplash(
                    'redirect'
                );
            }
        });
    });

    window.addEventListener('pageshow', () => {
        hidePaymentSplash();

        document.querySelectorAll(SUBMIT).forEach(submit => {
            submit.disabled = false;
            submit.removeAttribute('aria-busy');
        });
    });
};
