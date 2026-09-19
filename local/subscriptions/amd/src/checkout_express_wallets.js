import {
    hidePaymentSplash,
    showPaymentSplash,
} from './checkout_payment_splash';

// Commerce 7.96H4.1.8 — forced-fresh wallet probe.
const STRIPE_JS = 'https://js.stripe.com/v3/';

const loadStripe = () =>
    new Promise((resolve, reject) => {

        if (
            typeof window.Stripe
            === 'function'
        ) {
            resolve(window.Stripe);
            return;
        }

        const existing =
            document.querySelector(
                `script[src="${STRIPE_JS}"]`
            );

        if (existing) {
            existing.addEventListener(
                'load',
                () => resolve(window.Stripe),
                {once: true}
            );
            existing.addEventListener(
                'error',
                reject,
                {once: true}
            );
            return;
        }

        const script =
            document.createElement('script');

        script.src = STRIPE_JS;
        script.async = true;
        script.addEventListener(
            'load',
            () => resolve(window.Stripe),
            {once: true}
        );
        script.addEventListener(
            'error',
            reject,
            {once: true}
        );

        document.head.appendChild(
            script
        );
    });

const allowed = (
    config,
    method
) =>
    Array.isArray(config.methods)
    && config.methods.includes(method);

const expressPaymentMethodTypes = config => {
    const types = [];

    // Apple Pay and Google Pay use Stripe's card rail.
    if (
        allowed(config, 'apple_pay')
        || allowed(config, 'google_pay')
        || allowed(config, 'link')
    ) {
        types.push('card');
    }

    // Stripe requires Link to be enabled alongside card.
    if (allowed(config, 'link')) {
        types.push('link');
    }

    if (allowed(config, 'klarna')) {
        types.push('klarna');
    }

    return Array.from(
        new Set(types)
    );
};

const setResolvedState = (
    config,
    section,
    paymentMethods
) => {
    const available =
        paymentMethods
        && Object.values(
            paymentMethods
        ).some(Boolean);

    section.classList.remove(
        'is-probing',
        'is-wallet-ready',
        'is-wallet-unavailable'
    );

    if (available) {
        section.classList.add(
            'is-wallet-ready'
        );
        section.dataset.walletState =
            'ready';
    } else {
        section.classList.add(
            'is-wallet-unavailable'
        );
        section.dataset.walletState =
            'unavailable';
    }

};

const setError = (node, message) => {
    if (!(node instanceof HTMLElement)) {
        return;
    }

    node.textContent = String(message || '');
    node.hidden = node.textContent === '';
};

const selectedExpressMethod = event => {
    const type = String(
        event?.expressPaymentType
        || event?.paymentType
        || ''
    ).toLowerCase();

    if (type.includes('apple')) {
        return 'apple_pay';
    }

    if (type.includes('google')) {
        return 'google_pay';
    }

    if (type.includes('link')) {
        return 'link';
    }

    if (type.includes('klarna')) {
        return 'klarna';
    }

    return '';
};

const initializePayment = async (
    form,
    config,
    method
) => {
    const body = new FormData(form);
    body.set(
        'paymentmethod',
        method
    );
    body.set(
        'ajax',
        '1'
    );

    const response = await fetch(
        config.actionUrl,
        {
            method: 'POST',
            body,
            credentials:
                'same-origin',
            headers: {
                'X-Requested-With':
                    'XMLHttpRequest',
            },
        }
    );

    const payload =
        await response.json();

    if (
        !response.ok
        || !payload.ok
        || !payload.clientSecret
    ) {
        throw new Error(
            payload.error
            || 'Payment initialization failed.'
        );
    }

    return payload;
};

export const init = async config => {

    const form =
        document.querySelector(
            '[data-checkout-payment-form]'
        );
    const section =
        document.querySelector(
            '[data-checkout-express-wallet-section]'
        );
    const mount =
        document.querySelector(
            '[data-checkout-express-wallet-element]'
        );
    const errorNode =
        section?.querySelector(
            '[data-checkout-express-wallet-error]'
        );


    if (
        !form
        || !section
        || !mount
    ) {
        return;
    }

    try {
        const Stripe =
            await loadStripe(config);


        const stripe =
            Stripe(
                config.publishableKey,
                {
                    locale:
                        config.locale
                        || 'auto',
                }
            );

        // Mirror Stripe's documented deferred-Intent setup:
        // mode + amount + currency, with card explicitly enabled because
        // Apple Pay and Google Pay use the card rail.
        const elements =
            stripe.elements({
                mode: 'payment',
                amount:
                    Number(
                        config.amount
                    ),
                currency:
                    String(
                        config.currency
                    ).toLowerCase(),
                paymentMethodTypes:
                    expressPaymentMethodTypes(
                        config
                    ),
            });


        const expressOptions = {
            emailRequired: true,
            paymentMethods: {
                applePay:
                    allowed(
                        config,
                        'apple_pay'
                    )
                        ? 'always'
                        : 'never',
                googlePay:
                    allowed(
                        config,
                        'google_pay'
                    )
                        ? 'always'
                        : 'never',
            },
            buttonType: {
                googlePay: 'pay',
                klarna: 'pay',
            },
            buttonHeight: 50,
        };


        const express =
            elements.create(
                'expressCheckout',
                expressOptions
            );

        // H12.8.1.1: keep Stripe Express Checkout fully explorable before
        // consent so its own “show more” disclosure remains available. Legal
        // consent is enforced at the real payment confirmation boundary below.

        // H12.8.1.2: Stripe exposes a click event for the actual Express
        // payment method buttons. Rejecting here prevents Apple Pay / Google
        // Pay / Link / Klarna from opening before legal consent, without
        // covering the Element itself, so Stripe's own “show more” disclosure
        // remains freely usable.
        express.on(
            'click',
            event => {
                if (
                    form.dataset.guestPaymentLocked === '1'
                ) {
                    event.reject();
                    return;
                }

                const terms =
                    form.querySelector(
                        '[data-checkout-terms]'
                    );

                if (
                    terms instanceof HTMLInputElement
                    && !terms.checked
                ) {
                    event.reject();

                    form.dispatchEvent(
                        new CustomEvent(
                            'campusfr:checkout-consent-required',
                            {
                                bubbles: true,
                            }
                        )
                    );

                    return;
                }

                setError(errorNode, '');
                event.resolve();
            }
        );

        // Exact event/payload from Stripe's current documentation.
        express.on(
            'availablepaymentmethodschange',
            ({paymentMethods}) => {

                setResolvedState(
                    config,
                    section,
                    paymentMethods
                );
            }
        );

        express.on(
            'confirm',
            async event => {

                if (
                    form.dataset.guestPaymentLocked === '1'
                ) {
                    event.paymentFailed?.({
                        reason: 'invalid_request',
                    });
                    return;
                }

                if (
                    typeof form.checkValidity
                        === 'function'
                    && !form.checkValidity()
                ) {
                    const terms =
                        form.querySelector(
                            '[data-checkout-terms]'
                        );
                    const legalCard =
                        form.querySelector(
                            '[data-checkout-legal-card]'
                        );

                    if (
                        terms instanceof HTMLInputElement
                        && !terms.checked
                        && legalCard instanceof HTMLElement
                    ) {
                        legalCard.classList.add(
                            'is-required'
                        );
                        legalCard.dataset.consentState =
                            'required';
                        legalCard.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                        });

                        window.setTimeout(
                            () => terms.focus(),
                            180
                        );
                    }

                    form.reportValidity?.();
                    event?.paymentFailed?.({
                        reason: 'fail',
                    });
                    return;
                }

                const method =
                    selectedExpressMethod(
                        event
                    );

                if (
                    !method
                    || !allowed(
                        config,
                        method
                    )
                ) {
                    event?.paymentFailed?.({
                        reason: 'fail',
                    });
                    return;
                }

                try {
                    const {
                        error:
                            submitError,
                    } =
                        await elements.submit();

                    if (submitError) {
                        return;
                    }

                    const payload =
                        await initializePayment(
                            form,
                            config,
                            method
                        );

                    showPaymentSplash('processing');

                    const result =
                        await stripe
                            .confirmPayment({
                                elements,
                                clientSecret:
                                    payload
                                        .clientSecret,
                                confirmParams: {
                                    return_url:
                                        payload
                                            .returnUrl
                                        || config
                                            .returnUrl,
                                },
                                redirect:
                                    'if_required',
                            });

                    if (result.error) {
                        hidePaymentSplash();
                        event
                            ?.paymentFailed
                            ?.({
                                reason: 'fail',
                            });
                        return;
                    }

                    window.location.assign(
                        payload.returnUrl
                        || config.returnUrl
                    );
                } catch (error) {
                    hidePaymentSplash();
                    setError(
                        errorNode,
                        error instanceof Error
                            ? error.message
                            : 'Payment initialization failed.'
                    );
                    event?.paymentFailed?.({
                        reason: 'fail',
                    });
                }
            }
        );


        express.mount(mount);

        window.setTimeout(
            () => {
                if (
                    section.classList.contains(
                        'is-probing'
                    )
                ) {
                    section.classList.remove(
                        'is-probing'
                    );
                    section.classList.add(
                        'is-wallet-unavailable'
                    );
                    section.dataset.walletState =
                        'unavailable';
                }
            },
            12000
        );



    } catch (error) {
        section.classList.remove(
            'is-probing'
        );
        section.classList.add(
            'is-wallet-unavailable'
        );
        section.dataset.walletState =
            'unavailable';

        // Stripe Express Checkout is optional; keep the standard checkout available.
    }
};
