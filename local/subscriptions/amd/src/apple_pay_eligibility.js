// Commerce 7.96G2 — client eligibility for Apple Pay.

const METHOD_SELECTOR = '[data-payment-method="apple_pay"]';
const CARD_SELECTOR = 'input[name="paymentmethod"][value="card"]';

const canUseApplePay = () => {
    try {
        return typeof window.ApplePaySession !== 'undefined'
            && typeof window.ApplePaySession.canMakePayments === 'function'
            && window.ApplePaySession.canMakePayments();
    } catch (e) {
        return false;
    }
};

const fallBackToCard = (appleContainer) => {
    const appleRadio = appleContainer.querySelector(
        'input[name="paymentmethod"][value="apple_pay"]'
    );

    if (!appleRadio || !appleRadio.checked) {
        return;
    }

    const cardRadio = document.querySelector(CARD_SELECTOR);
    if (!cardRadio) {
        appleRadio.checked = false;
        return;
    }

    appleRadio.checked = false;
    cardRadio.checked = true;
    cardRadio.dispatchEvent(
        new Event('change', {bubbles: true})
    );
};

export const init = () => {
    const methods = Array.from(
        document.querySelectorAll(METHOD_SELECTOR)
    );

    if (!methods.length) {
        return;
    }

    const eligible = canUseApplePay();

    methods.forEach(container => {
        if (eligible) {
            container.classList.add('is-client-eligible');
            container.removeAttribute('aria-hidden');
            return;
        }

        container.classList.remove('is-client-eligible');
        container.setAttribute('aria-hidden', 'true');
        fallBackToCard(container);
    });
};
