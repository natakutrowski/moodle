// Commerce 7.96H3 — Stripe Payment Element + direct Apple/Google wallets.

const STRIPE_JS = 'https://js.stripe.com/v3/';

const loadStripe = () => new Promise((resolve, reject) => {
    if (typeof window.Stripe === 'function') {
        resolve(window.Stripe);
        return;
    }

    const existing = document.querySelector(`script[src="${STRIPE_JS}"]`);
    if (existing) {
        existing.addEventListener('load', () => resolve(window.Stripe), {once: true});
        existing.addEventListener('error', reject, {once: true});
        return;
    }

    const script = document.createElement('script');
    script.src = STRIPE_JS;
    script.async = true;
    script.addEventListener('load', () => resolve(window.Stripe), {once: true});
    script.addEventListener('error', reject, {once: true});
    document.head.appendChild(script);
});

const setSplash = (visible, title = '', message = '') => {
    const splash = document.querySelector('[data-payment-splash]');
    if (!splash) {
        return;
    }

    splash.classList.toggle('is-visible', visible);
    splash.setAttribute('aria-hidden', visible ? 'false' : 'true');

    if (title) {
        splash.querySelector('[data-payment-splash-title]').textContent = title;
    }
    if (message) {
        splash.querySelector('[data-payment-splash-message]').textContent = message;
    }
};

const processingCopy = () => {
    const source = document.querySelector('[data-payment-splash-processing-title]');
    return {
        title: source?.dataset.paymentSplashProcessingTitle || '',
        message: source?.dataset.paymentSplashProcessingMessage || '',
    };
};

const showError = message => {
    const target = document.querySelector('[data-stripe-payment-error]');
    if (!target) {
        return;
    }

    target.textContent = String(message || '');
    target.hidden = !message;
};

const allowed = (config, method) =>
    Array.isArray(config.expressMethods)
    && config.expressMethods.includes(method);

const expressOptions = config => ({
    paymentMethods: {
        applePay: allowed(config, 'apple_pay') ? 'auto' : 'never',
        googlePay: allowed(config, 'google_pay') ? 'auto' : 'never',
    },
    buttonType: {
        applePay: 'buy',
        googlePay: 'pay',
    },
    buttonHeight: 50,
    layout: {
        maxColumns: 2,
        maxRows: 1,
        overflow: 'never',
    },
});

const confirmPayment = async (
    stripe,
    elements,
    config,
    submit = null
) => {
    showError('');

    if (submit) {
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
    }

    const copy = processingCopy();
    setSplash(true, copy.title, copy.message);

    const result = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: config.returnUrl,
        },
        redirect: 'if_required',
    });

    if (result.error) {
        setSplash(false);

        if (submit) {
            submit.disabled = false;
            submit.removeAttribute('aria-busy');
        }

        showError(
            result.error.message
            || 'Payment could not be confirmed.'
        );
        return;
    }

    window.location.assign(config.returnUrl);
};

export const init = async config => {
    const form = document.querySelector('[data-stripe-embedded-form]');
    const mount = document.querySelector('[data-stripe-payment-element]');
    const submit = document.querySelector('[data-stripe-payment-submit]');
    const expressMount = document.querySelector('[data-stripe-express-element]');
    const expressSection = document.querySelector('[data-stripe-express-section]');

    if (!form || !mount || !submit) {
        setSplash(false);
        return;
    }

    try {
        const Stripe = await loadStripe();
        const stripe = Stripe(config.publishableKey);
        const elements = stripe.elements({
            clientSecret: config.clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    borderRadius: '10px',
                },
            },
        });

        if (
            expressMount
            && expressSection
            && (
                allowed(config, 'apple_pay')
                || allowed(config, 'google_pay')
            )
        ) {
            const expressCheckout = elements.create(
                'expressCheckout',
                expressOptions(config)
            );

            expressCheckout.on(
                'availablepaymentmethodschange',
                ({paymentMethods}) => {
                    const hasWallet =
                        paymentMethods
                        && Object.values(paymentMethods)
                            .some(Boolean);

                    expressSection.hidden = !hasWallet;
                }
            );

            expressCheckout.on('confirm', async () => {
                const {error: submitError} =
                    await elements.submit();

                if (submitError) {
                    showError(
                        submitError.message
                        || 'Wallet could not be confirmed.'
                    );
                    return;
                }

                await confirmPayment(
                    stripe,
                    elements,
                    config
                );
            });

            expressCheckout.mount(expressMount);
        }

        const paymentElementOptions = {
            layout: 'tabs',
        };

        if (
            Array.isArray(
                config.paymentElementMethods
            )
            && config.paymentElementMethods.length > 0
        ) {
            paymentElementOptions.paymentMethodOrder =
                config.paymentElementMethods;
        }

        const paymentElement = elements.create(
            'payment',
            paymentElementOptions
        );

        paymentElement.mount(mount);
        paymentElement.on(
            'ready',
            () => setSplash(false)
        );

        form.addEventListener('submit', async event => {
            event.preventDefault();

            if (submit.disabled) {
                return;
            }

            const {error: submitError} =
                await elements.submit();

            if (submitError) {
                showError(
                    submitError.message
                    || 'Payment details are incomplete.'
                );
                return;
            }

            await confirmPayment(
                stripe,
                elements,
                config,
                submit
            );
        });
    } catch (error) {
        setSplash(false);
        submit.disabled = false;
        submit.removeAttribute('aria-busy');
        showError(
            error?.message
            || 'Payment form could not be loaded.'
        );
    }
};
