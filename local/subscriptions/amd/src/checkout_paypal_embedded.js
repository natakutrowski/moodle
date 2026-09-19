// Commerce 7.96H12.8.1 — PayPal JS SDK v6 embedded/popup rail.

import {
    hidePaymentSplash,
    showPaymentSplash,
} from './checkout_payment_splash';

const FORM = '[data-checkout-payment-form]';
const CONFIG = '[data-checkout-paypal-embedded]';
const ERROR = '[data-checkout-paypal-error]';

let sdkPromise = null;

const loadSdk = url => {
    if (
        window.paypal
        && typeof window.paypal.createInstance
            === 'function'
    ) {
        return Promise.resolve(
            window.paypal
        );
    }

    if (sdkPromise !== null) {
        return sdkPromise;
    }

    sdkPromise =
        new Promise(
            (resolve, reject) => {
                const script =
                    document.createElement(
                        'script'
                    );

                script.src = url;
                script.async = true;
                script.dataset.campusfrPaypalSdk =
                    'v6';

                script.addEventListener(
                    'load',
                    () => {
                        if (
                            window.paypal
                            && typeof window.paypal
                                .createInstance
                                === 'function'
                        ) {
                            resolve(
                                window.paypal
                            );
                            return;
                        }

                        reject(
                            new Error(
                                'PayPal SDK did not expose createInstance.'
                            )
                        );
                    },
                    {
                        once: true,
                    }
                );

                script.addEventListener(
                    'error',
                    () => reject(
                        new Error(
                            'PayPal SDK could not be loaded.'
                        )
                    ),
                    {
                        once: true,
                    }
                );

                document.head.appendChild(
                    script
                );
            }
        );

    return sdkPromise;
};

const setFeedback = (
    node,
    message,
    type = 'danger'
) => {
    if (!(node instanceof HTMLElement)) {
        return;
    }

    node.textContent =
        String(
            message
            || ''
        );
    node.classList.toggle(
        'alert-danger',
        type === 'danger'
    );
    node.classList.toggle(
        'alert-info',
        type === 'info'
    );
    node.hidden =
        node.textContent === '';
};

const setError = (
    node,
    message
) => setFeedback(
    node,
    message,
    'danger'
);


const cancellationFeedbackNode = (
    form,
    configNode
) => {
    let node =
        form.querySelector(
            '[data-checkout-paypal-cancel-feedback]'
        );

    if (node instanceof HTMLElement) {
        return node;
    }

    node =
        document.createElement(
            'div'
        );
    node.className =
        'alert alert-info small commerce-checkout-paypal-cancel-feedback';
    node.dataset.checkoutPaypalCancelFeedback =
        '1';
    node.setAttribute(
        'role',
        'status'
    );
    node.setAttribute(
        'aria-live',
        'polite'
    );
    node.hidden = true;

    const paypalAction =
        form.querySelector(
            '[data-payment-method="paypal"]'
        )
        || form.querySelector(
            '[data-payment-method-card="paypal"]'
        );

    if (paypalAction instanceof HTMLElement) {
        const paypalCard =
            paypalAction.closest(
                '[data-payment-action-card]'
            )
            || paypalAction;

        paypalCard.insertAdjacentElement(
            'afterend',
            node
        );
    } else {
        const paymentSection =
            form.querySelector(
                '[data-checkout-payment-methods]'
            )
            || configNode.parentElement;

        if (paymentSection instanceof HTMLElement) {
            paymentSection.insertAdjacentElement(
                'afterend',
                node
            );
        } else {
            form.appendChild(
                node
            );
        }
    }

    return node;
};

const createOrder = async (
    form,
    config
) => {
    const body =
        new FormData(
            form
        );

    body.set(
        'paymentmethod',
        'paypal'
    );
    body.set(
        'ajax',
        '1'
    );
    body.set(
        'checkoutlanguage',
        String(
            document.documentElement.lang
            || ''
        )
    );

    const response =
        await fetch(
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
        payload =
            await response.json();
    } catch (error) {
        throw new Error(
            config.prepareError
        );
    }

    if (
        !response.ok
        || !payload.ok
        || payload.type
            !== 'paypal_order'
        || !payload.orderId
    ) {
        throw new Error(
            payload?.error
            || config.prepareError
        );
    }

    return payload;
};

const approvedReturnUrl = (
    base,
    orderId
) => {
    const url =
        new URL(
            base,
            window.location.href
        );

    url.searchParams.set(
        'orderId',
        orderId
    );

    return url.href;
};

export const init = async () => {
    const form =
        document.querySelector(
            FORM
        );
    const configNode =
        document.querySelector(
            CONFIG
        );
    const errorNode =
        configNode?.querySelector(
            ERROR
        );

    if (
        !(form instanceof HTMLFormElement)
        || !(configNode instanceof HTMLElement)
    ) {
        return;
    }

    const cancelFeedbackNode =
        cancellationFeedbackNode(
            form,
            configNode
        );

    const clearCancelFeedback = () => {
        setFeedback(
            cancelFeedbackNode,
            '',
            'info'
        );
    };

    form.addEventListener(
        'click',
        event => {
            const target =
                event.target;

            if (
                !(target instanceof Element)
                || !target.closest(
                    '[data-payment-method], [data-payment-method-card], [data-payment-action-card]'
                )
            ) {
                return;
            }

            clearCancelFeedback();
        }
    );

    form.addEventListener(
        'change',
        event => {
            const target =
                event.target;

            if (
                target instanceof HTMLInputElement
                && target.name === 'paymentmethod'
            ) {
                clearCancelFeedback();
            }
        }
    );

    const config = {
        sdkUrl:
            String(
                configNode.dataset.sdkUrl
                || ''
            ),
        clientId:
            String(
                configNode.dataset.clientId
                || ''
            ),
        currency:
            String(
                configNode.dataset.currency
                || ''
            ).toUpperCase(),
        locale:
            String(
                configNode.dataset.locale
                || 'en-US'
            ),
        actionUrl:
            String(
                configNode.dataset.actionUrl
                || ''
            ),
        prepareError:
            String(
                configNode.dataset.prepareError
                || ''
            ),
        popupError:
            String(
                configNode.dataset.popupError
                || ''
            ),
        cancelledMessage:
            String(
                configNode.dataset.cancelledMessage
                || ''
            ),
    };

    if (
        config.sdkUrl === ''
        || config.clientId === ''
        || config.actionUrl === ''
        || config.currency === ''
    ) {
        return;
    }

    let session = null;
    let eligible = false;
    let activeOrder = null;
    let starting = false;
    let startGeneration = 0;

    try {
        const paypal =
            await loadSdk(
                config.sdkUrl
            );

        const sdk =
            await paypal.createInstance({
                clientId:
                    config.clientId,
                components: [
                    'paypal-payments',
                ],
                pageType:
                    'checkout',
                locale:
                    config.locale,
            });

        const methods =
            await sdk.findEligibleMethods({
                currencyCode:
                    config.currency,
            });

        eligible =
            methods.isEligible(
                'paypal'
            );

        if (!eligible) {
            return;
        }

        session =
            sdk.createPayPalOneTimePaymentSession({
                onApprove:
                    async ({orderId}) => {
                        const resolvedOrderId =
                            String(
                                orderId
                                || activeOrder?.orderId
                                || ''
                            );

                        if (
                            resolvedOrderId === ''
                            || !activeOrder?.returnUrl
                        ) {
                            setError(
                                errorNode,
                                config.prepareError
                            );
                            return;
                        }

                        showPaymentSplash(
                            'validated'
                        );

                        window.location.assign(
                            approvedReturnUrl(
                                activeOrder.returnUrl,
                                resolvedOrderId
                            )
                        );
                    },

                onCancel:
                    () => {
                        startGeneration += 1;
                        starting = false;
                        activeOrder = null;
                        hidePaymentSplash();
                        setFeedback(
                            cancelFeedbackNode,
                            config.cancelledMessage,
                            'info'
                        );
                    },

                onError:
                    () => {
                        startGeneration += 1;
                        starting = false;
                        activeOrder = null;
                        hidePaymentSplash();
                        setError(
                            errorNode,
                            config.popupError
                        );
                    },
            });
    } catch (error) {
        // SDK/eligibility failure deliberately leaves the existing hosted
        // PayPal redirect available as the safe fallback.
        eligible = false;
        session = null;
    }

    form.addEventListener(
        'campusfr:payment-intent-execute',
        event => {
            const method =
                String(
                    event.detail?.method
                    || ''
                ).toLowerCase();

            if (
                method !== 'paypal'
                || !eligible
                || session === null
            ) {
                return;
            }

            event.preventDefault();

            if (starting) {
                return;
            }

            starting = true;
            startGeneration += 1;

            const generation =
                startGeneration;

            activeOrder = null;

            setError(
                errorNode,
                ''
            );
            clearCancelFeedback();

            showPaymentSplash(
                'preparing'
            );

            const orderPayloadPromise =
                createOrder(
                    form,
                    config
                ).then(payload => {
                    if (
                        generation
                        !== startGeneration
                    ) {
                        throw new Error(
                            'PayPal start superseded.'
                        );
                    }

                    activeOrder =
                        payload;

                    // PayPal now owns the visible payment surface.
                    hidePaymentSplash();

                    return {
                        orderId:
                            payload.orderId,
                    };
                });

            session.start(
                {
                    presentationMode:
                        'auto',
                },
                orderPayloadPromise
            ).then(
                () => {
                    starting = false;
                }
            ).catch(
                async startError => {
                    if (
                        generation
                        !== startGeneration
                    ) {
                        return;
                    }

                    starting = false;
                    hidePaymentSplash();

                    let feedback =
                        startError instanceof Error
                            ? startError.message
                            : '';

                    try {
                        const payload =
                            activeOrder
                            || await orderPayloadPromise;

                        if (payload?.fallbackUrl) {
                            showPaymentSplash(
                                'redirect'
                            );

                            window.location.assign(
                                payload.fallbackUrl
                            );
                            return;
                        }
                    } catch (error) {
                        if (
                            error instanceof Error
                            && error.message !== ''
                        ) {
                            feedback =
                                error.message;
                        }
                    }

                    activeOrder = null;

                    setError(
                        errorNode,
                        feedback
                        || config.popupError
                        || config.prepareError
                    );
                }
            );
        }
    );
};
