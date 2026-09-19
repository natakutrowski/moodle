// Commerce 7.96H12.6.1 — Alfa SBP QR embedded directly in CampusFR.

import {
    showPaymentSplash,
} from './checkout_payment_splash';

const POLL_INTERVAL_MS = 3000;
const POLL_MAX_MS = 5 * 60 * 1000;

const selectedMethod = form => {
    const selected = form.querySelector(
        'input[name="paymentmethod"]:checked'
    );

    return selected
        ? String(selected.value || '').toLowerCase()
        : '';
};

const setSubmitLabel = (submit, label) => {
    const node = submit.querySelector(
        '[data-checkout-submit-label]'
    );

    if (node) {
        node.textContent = String(label || '');
    }
};

const setError = (node, message) => {
    if (!(node instanceof HTMLElement)) {
        return;
    }

    node.textContent = String(message || '');
    node.hidden = !message;
};

const safeUrl = value => {
    try {
        const url = new URL(
            String(value || ''),
            window.location.href
        );

        return ['https:', 'http:'].includes(
            url.protocol
        )
            ? url.href
            : '';
    } catch (error) {
        return '';
    }
};

const prepare = async (
    form,
    config
) => {
    const body = new FormData(form);
    body.set(
        'paymentmethod',
        'sbp'
    );
    body.set(
        'ajax',
        '1'
    );
    body.set(
        'checkoutlanguage',
        String(
            config.checkoutLanguage
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
        || payload.type !== 'alfa_sbp'
        || !payload.parameters
    ) {
        throw new Error(
            payload?.error
            || config.prepareError
        );
    }

    return payload.parameters;
};

const checkStatus = async statusUrl => {
    const response =
        await fetch(
            statusUrl,
            {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
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
            'Invalid SBP status response.'
        );
    }

    if (!response.ok || !payload.ok) {
        throw new Error(
            'SBP status is temporarily unavailable.'
        );
    }

    return payload;
};

export const init = () => {
    const form =
        document.querySelector(
            '[data-checkout-payment-form]'
        );
    const panel =
        document.querySelector(
            '[data-checkout-alfa-sbp]'
        );
    const qr =
        document.querySelector(
            '[data-checkout-alfa-sbp-qr]'
        );
    const open =
        document.querySelector(
            '[data-checkout-alfa-sbp-open]'
        );
    const verify =
        document.querySelector(
            '[data-checkout-alfa-sbp-verify]'
        );
    const errorbox =
        document.querySelector(
            '[data-checkout-alfa-sbp-error]'
        );
    const loading =
        document.querySelector(
            '[data-checkout-alfa-sbp-loading]'
        );
    const content =
        document.querySelector(
            '[data-checkout-alfa-sbp-content]'
        );
    const submit =
        form?.querySelector(
            '[data-checkout-submit]'
        );
    const quickButton =
        form?.querySelector(
            '[data-quick-payment-action="sbp"]'
        );
    const quickRadio =
        form?.querySelector(
            '[data-quick-payment-radio="sbp"]'
        );

    const config =
        panel instanceof HTMLElement
            ? {
                actionUrl:
                    panel.dataset.actionUrl
                    || '',
                checkoutLanguage:
                    panel.dataset.checkoutLanguage
                    || '',
                prepareError:
                    panel.dataset.prepareError
                    || '',
                openBankLabel:
                    panel.dataset.openBankLabel
                    || '',
                verifyLabel:
                    panel.dataset.verifyLabel
                    || '',
                defaultLabel:
                    panel.dataset.defaultLabel
                    || '',
            }
            : {};

    if (
        !(form instanceof HTMLFormElement)
        || !(panel instanceof HTMLElement)
        || !(submit instanceof HTMLButtonElement)
    ) {
        return;
    }

    let prepared = false;
    let preparing = false;
    let statusUrl = '';
    let returnUrl = '';
    let pollTimer = null;
    let pollStartedAt = 0;
    let statusChecking = false;

    const stopPolling = () => {
        if (pollTimer !== null) {
            window.clearTimeout(
                pollTimer
            );
            pollTimer = null;
        }
    };

    const setPreparingSurface =
        visible => {
            if (
                loading
                instanceof HTMLElement
            ) {
                loading.hidden =
                    !visible;
            }

            if (
                content
                instanceof HTMLElement
            ) {
                content.hidden =
                    visible;
            }

            if (visible) {
                panel.hidden = false;
            }
        };

    const refresh = () => {
        const active =
            selectedMethod(form)
            === 'sbp';

        panel.hidden =
            !active
            || (!prepared && !preparing);

        if (!active) {
            setPreparingSurface(false);
            submit.hidden = true;
            submit.disabled = false;
            setSubmitLabel(
                submit,
                config.defaultLabel
            );
            stopPolling();
            return;
        }

        if (prepared) {
            submit.hidden = true;
            return;
        }

        submit.hidden = false;
        submit.disabled = preparing;
        setSubmitLabel(
            submit,
            config.defaultLabel
        );
    };

    const navigateAfterPaid = () => {
        stopPolling();

        showPaymentSplash(
            'processing'
        );

        if (returnUrl !== '') {
            window.location.assign(
                returnUrl
            );
        }
    };

    const pollOnce = async (
        manual = false
    ) => {
        if (
            statusChecking
            || statusUrl === ''
            || selectedMethod(form)
                !== 'sbp'
        ) {
            return;
        }

        statusChecking = true;

        try {
            const status =
                await checkStatus(
                    statusUrl
                );

            if (
                status.state === 'paid'
                || status.complete === true
            ) {
                navigateAfterPaid();
                return;
            }

            if (
                manual
                && status.state !== 'paid'
            ) {
                setError(
                    errorbox,
                    ''
                );
            }
        } catch (error) {
            if (manual) {
                setError(
                    errorbox,
                    error?.message
                    || config.prepareError
                );
            }
        } finally {
            statusChecking = false;
        }
    };

    const schedulePolling = () => {
        stopPolling();

        if (
            statusUrl === ''
            || selectedMethod(form)
                !== 'sbp'
        ) {
            return;
        }

        if (pollStartedAt === 0) {
            pollStartedAt =
                Date.now();
        }

        if (
            Date.now()
            - pollStartedAt
            >= POLL_MAX_MS
        ) {
            return;
        }

        pollTimer =
            window.setTimeout(
                async () => {
                    await pollOnce();

                    if (
                        selectedMethod(form)
                        === 'sbp'
                    ) {
                        schedulePolling();
                    }
                },
                POLL_INTERVAL_MS
            );
    };

    const renderParameters =
        parameters => {
            const qrsrc =
                String(
                    parameters.rendered_qr
                    || ''
                ).trim();
            const payloadurl =
                safeUrl(
                    parameters.payload
                );

            returnUrl =
                safeUrl(
                    parameters.return_url
                );
            statusUrl =
                safeUrl(
                    parameters.status_url
                );

            if (
                qr
                instanceof HTMLImageElement
            ) {
                qr.hidden =
                    qrsrc === '';

                if (qrsrc !== '') {
                    qr.src =
                        qrsrc;
                }
            }

            if (
                open
                instanceof HTMLAnchorElement
            ) {
                open.hidden =
                    payloadurl === '';

                if (payloadurl !== '') {
                    open.href =
                        payloadurl;
                    open.textContent =
                        String(
                            config.openBankLabel
                            || open.textContent
                        );
                }
            }

            if (
                verify
                instanceof HTMLAnchorElement
            ) {
                verify.hidden =
                    statusUrl === ''
                    && returnUrl === '';

                verify.href =
                    returnUrl !== ''
                        ? returnUrl
                        : '#';

                verify.textContent =
                    String(
                        config.verifyLabel
                        || verify.textContent
                    );
            }

            if (
                qrsrc === ''
                && payloadurl === ''
            ) {
                throw new Error(
                    config.prepareError
                );
            }
        };

    const launch = async () => {
        if (
            selectedMethod(form)
            !== 'sbp'
        ) {
            return;
        }

        if (
            typeof form.checkValidity
            === 'function'
            && !form.checkValidity()
        ) {
            form.reportValidity?.();
            return;
        }

        if (prepared) {
            panel.hidden = false;
            schedulePolling();
            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
            });
            return;
        }

        if (preparing) {
            return;
        }

        preparing = true;
        setError(
            errorbox,
            ''
        );
        setPreparingSurface(
            true
        );
        refresh();

        try {
            const parameters =
                await prepare(
                    form,
                    config
                );

            renderParameters(
                parameters
            );

            prepared = true;
            pollStartedAt =
                Date.now();

            setPreparingSurface(
                false
            );

            const stillSelected =
                selectedMethod(form)
                === 'sbp';

            panel.hidden =
                !stillSelected;

            if (stillSelected) {
                schedulePolling();

                panel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            }
        } catch (error) {
            setPreparingSurface(
                false
            );

            const stillSelected =
                selectedMethod(form)
                === 'sbp';

            panel.hidden =
                !stillSelected;

            if (stillSelected) {
                setError(
                    errorbox,
                    error?.message
                    || config.prepareError
                );
            }
        } finally {
            preparing = false;
            refresh();
        }
    };

    // SBP owns its quick-action click. The generic Mustache handler would
    // otherwise submit commerce_checkout_action.php as a normal page before
    // the embedded QR driver can perform its AJAX initialization.
    if (
        quickButton
        instanceof HTMLButtonElement
    ) {
        quickButton.addEventListener(
            'click',
            async event => {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                if (
                    quickRadio
                    instanceof HTMLInputElement
                ) {
                    quickRadio.checked =
                        true;
                    quickRadio.dispatchEvent(
                        new Event(
                            'change',
                            {
                                bubbles: true,
                            }
                        )
                    );
                }

                await launch();
            },
            true
        );
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
                method === 'sbp'
                && prepared
            ) {
                event.preventDefault();
                panel.hidden = false;
                schedulePolling();

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
            () => {
                refresh();

                if (
                    selectedMethod(form)
                    === 'sbp'
                    && prepared
                ) {
                    panel.hidden = false;
                    schedulePolling();
                }
            }
        );
    });

    if (
        verify
        instanceof HTMLAnchorElement
    ) {
        verify.addEventListener(
            'click',
            async event => {
                if (statusUrl === '') {
                    return;
                }

                event.preventDefault();

                await pollOnce(
                    true
                );
            }
        );
    }

    form.addEventListener(
        'submit',
        async event => {
            if (
                selectedMethod(form)
                !== 'sbp'
            ) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            await launch();
        }
    );

    window.addEventListener(
        'pagehide',
        stopPolling
    );

    refresh();
};
