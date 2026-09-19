import {
    hidePaymentSplash,
    showPaymentSplash,
} from './checkout_payment_splash';

// Commerce 7.96H5 — direct card entry inside the main CampusFR checkout.

const STRIPE_JS = 'https://js.stripe.com/v3/';

const loadStripe = () =>
    new Promise((resolve, reject) => {
        if (typeof window.Stripe === 'function') {
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

        document.head.appendChild(script);
    });

const isInlineMethod = (config, method) =>
    Array.isArray(config.inlineMethods)
    && config.inlineMethods.includes(method);

const selectedMethod = form => {
    const selected =
        form.querySelector(
            'input[name="paymentmethod"]:checked'
        );

    return selected
        ? String(selected.value || '').toLowerCase()
        : '';
};

const showError = (errorbox, message) => {
    if (!errorbox) {
        return;
    }

    errorbox.textContent =
        String(message || '');
    errorbox.hidden = !message;
};

const setSubmitLabel = (
    submit,
    label
) => {
    const text =
        submit.querySelector(
            '[data-checkout-submit-label]'
        );

    if (text) {
        text.textContent = label;
        return;
    }

    submit.childNodes.forEach(node => {
        if (
            node.nodeType
            === Node.TEXT_NODE
            && node.textContent.trim() !== ''
        ) {
            node.textContent = label;
        }
    });
};

const setBusy = (
    submit,
    busy
) => {
    submit.disabled = busy;
    submit.setAttribute(
        'aria-busy',
        busy ? 'true' : 'false'
    );
};

const prepareInline = async (
    form,
    config,
    prepareError
) => {
    const body = new FormData(form);
    body.set(
        'paymentmethod',
        selectedMethod(form)
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
            credentials: 'same-origin',
            headers: {
                'X-Requested-With':
                    'XMLHttpRequest',
            },
        }
    );

    let payload;

    try {
        payload = await response.json();
    } catch (error) {
        throw new Error(
            prepareError
        );
    }

    if (
        !response.ok
        || !payload.ok
        || payload.type !== 'embedded'
        || !payload.clientSecret
        || !payload.publishableKey
    ) {
        throw new Error(
            payload?.error
            || prepareError
        );
    }

    return payload;
};

export const init = config => {
    const form =
        document.querySelector(
            '[data-checkout-payment-form]'
        );
    const panel =
        document.querySelector(
            '[data-checkout-inline-card]'
        );
    const mount =
        document.querySelector(
            '[data-checkout-inline-card-element]'
        );
    const errorbox =
        document.querySelector(
            '[data-checkout-inline-card-error]'
        );
    const submit =
        form?.querySelector(
            '[data-checkout-submit]'
        );
    const hasinlinemethod =
        Array.from(
            form?.querySelectorAll(
                'input[name="paymentmethod"]'
            )
            || []
        ).some(
            input =>
                isInlineMethod(
                    config,
                    String(input.value || '')
                )
        );

    if (
        !(form instanceof HTMLFormElement)
        || !(panel instanceof HTMLElement)
        || !(mount instanceof HTMLElement)
        || !(submit instanceof HTMLButtonElement)
        || !hasinlinemethod
    ) {
        return;
    }

    let stripe = null;
    let elements = null;
    let paymentElement = null;
    let returnUrl = '';
    let prepared = false;
    let preparedMethod = '';
    let preparing = false;
    let confirming = false;

    // H12.3.1: localised copy is rendered in the DOM instead of being passed
    // through js_call_amd(). This keeps the AMD bootstrap compact in all
    // languages, especially Russian.
    const submitLabelNode =
        submit.querySelector(
            '[data-checkout-submit-label]'
        );
    const originalLabel =
        String(
            submitLabelNode?.textContent
            || ''
        ).trim();
    const openLabel =
        String(
            panel.dataset.openLabel
            || originalLabel
        ).trim();
    const confirmLabel =
        String(
            panel.dataset.confirmLabel
            || originalLabel
        ).trim();
    const prepareError =
        String(
            panel.dataset.prepareError
            || ''
        ).trim();
    const confirmError =
        String(
            panel.dataset.confirmError
            || ''
        ).trim();

    const refreshForMethod = () => {
        const method =
            selectedMethod(form);

        if (
            !isInlineMethod(
                config,
                method
            )
        ) {
            panel.hidden = true;
            showError(
                errorbox,
                ''
            );

            submit.hidden = true;

            if (!confirming) {
                setSubmitLabel(
                    submit,
                    originalLabel
                );
            }

            return;
        }

        if (
            prepared
            && preparedMethod === method
        ) {
            panel.hidden = false;
            submit.hidden = false;
            setSubmitLabel(
                submit,
                confirmLabel
            );
        } else {
            panel.hidden = true;
            submit.hidden = false;
            setSubmitLabel(
                submit,
                openLabel
            );
        }
    };

    form.addEventListener(
        'campusfr:payment-intent-execute',
        event => {
            const method =
                String(
                    event.detail?.method
                    || ''
                ).toLowerCase();

            if (
                prepared
                && preparedMethod === method
                && isInlineMethod(
                    config,
                    method
                )
            ) {
                event.preventDefault();
                panel.hidden = false;
                submit.hidden = false;
                setSubmitLabel(
                    submit,
                    confirmLabel
                );
                panel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            }
        }
    );

    form.querySelectorAll(
        'input[name="paymentmethod"]'
    ).forEach(input => {
        input.addEventListener(
            'change',
            refreshForMethod
        );
    });

    form.addEventListener(
        'submit',
        async event => {
            const method =
                selectedMethod(form);

            if (
                !isInlineMethod(
                    config,
                    method
                )
            ) {
                return;
            }

            // Run before the generic submit-state handler and retain the
            // customer on the CampusFR checkout.
            event.preventDefault();
            event.stopImmediatePropagation();

            if (
                typeof form.checkValidity
                    === 'function'
                && !form.checkValidity()
            ) {
                form.reportValidity?.();
                return;
            }

            if (
                preparing
                || confirming
            ) {
                return;
            }

            showError(
                errorbox,
                ''
            );

            if (
                prepared
                && preparedMethod !== method
            ) {
                paymentElement?.unmount?.();
                mount.replaceChildren();
                stripe = null;
                elements = null;
                paymentElement = null;
                returnUrl = '';
                prepared = false;
                preparedMethod = '';
            }

            if (!prepared) {
                const requestedMethod =
                    method;

                preparing = true;
                setBusy(
                    submit,
                    true
                );

                try {
                    const payload =
                        await prepareInline(
                            form,
                            config,
                            prepareError
                        );

                    if (
                        selectedMethod(form)
                        !== requestedMethod
                    ) {
                        return;
                    }

                    const Stripe =
                        await loadStripe();

                    stripe =
                        Stripe(
                            payload.publishableKey,
                            {
                                locale:
                                    config.locale
                                    || 'auto',
                            }
                        );

                    elements =
                        stripe.elements({
                            clientSecret:
                                payload.clientSecret,
                            appearance: {
                                theme: 'stripe',
                                variables: {
                                    borderRadius:
                                        '10px',
                                },
                            },
                        });

                    const email =
                        String(
                            form.querySelector(
                                '[name="email"]'
                            )?.value
                            || ''
                        ).trim();
                    const firstname =
                        String(
                            form.querySelector(
                                '[name="firstname"]'
                            )?.value
                            || ''
                        ).trim();
                    const lastname =
                        String(
                            form.querySelector(
                                '[name="lastname"]'
                            )?.value
                            || ''
                        ).trim();
                    const fullname =
                        [firstname, lastname]
                            .filter(Boolean)
                            .join(' ');

                    paymentElement =
                        elements.create(
                            'payment',
                            {
                                layout: 'tabs',
                                paymentMethodOrder:
                                    ['card'],
                                wallets: {
                                    applePay: 'never',
                                    googlePay: 'never',
                                    link: 'never',
                                },
                                defaultValues: {
                                    billingDetails: {
                                        email,
                                        name: fullname,
                                    },
                                },
                            }
                        );

                    panel.hidden = false;
                    paymentElement.mount(
                        mount
                    );

                    returnUrl =
                        String(
                            payload.returnUrl
                            || config.returnUrl
                            || ''
                        );

                    prepared = true;
                    preparedMethod = method;

                    setSubmitLabel(
                        submit,
                        confirmLabel
                    );

                    panel.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                    });
                } catch (error) {
                    panel.hidden = false;
                    showError(
                        errorbox,
                        error?.message
                        || prepareError
                    );
                } finally {
                    preparing = false;
                    setBusy(
                        submit,
                        false
                    );
                }

                return;
            }

            confirming = true;
            setBusy(
                submit,
                true
            );

            try {
                showPaymentSplash(
                    'processing'
                );

                const {
                    error:
                        submitError,
                } =
                    await elements.submit();

                if (submitError) {
                    throw submitError;
                }

                const result =
                    await stripe.confirmPayment({
                        elements,
                        confirmParams: {
                            return_url:
                                returnUrl,
                        },
                        redirect:
                            'if_required',
                    });

                if (result.error) {
                    throw result.error;
                }

                window.location.assign(
                    returnUrl
                );
            } catch (error) {
                hidePaymentSplash();
                confirming = false;
                setBusy(
                    submit,
                    false
                );
                showError(
                    errorbox,
                    error?.message
                    || confirmError
                );
            }
        },
        true
    );

    window.addEventListener(
        'pageshow',
        () => {
            hidePaymentSplash();
            if (!confirming) {
                setBusy(
                    submit,
                    false
                );
                refreshForMethod();
            }
        }
    );

    refreshForMethod();
};
