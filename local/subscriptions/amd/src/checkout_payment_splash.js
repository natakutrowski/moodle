// Commerce 7.96H12.6 — provider-agnostic checkout payment splash.

const SELECTOR =
    '[data-checkout-payment-splash], [data-checkout-wallet-splash]';

export const getPaymentSplash = () =>
    document.querySelector(
        SELECTOR
    );

export const showPaymentSplash = (
    state = 'processing'
) => {
    const splash =
        getPaymentSplash();

    if (!(splash instanceof HTMLElement)) {
        return;
    }

    const resolvedState =
        String(
            state
            || 'processing'
        );

    splash.dataset.paymentState =
        resolvedState;

    const title =
        splash.querySelector(
            '[data-payment-splash-title]'
        );
    const message =
        splash.querySelector(
            '[data-payment-splash-message]'
        );

    if (title instanceof HTMLElement) {
        const validatedTitle =
            String(
                title.dataset.validatedTitle
                || ''
            );
        const processingTitle =
            String(
                title.dataset.processingTitle
                || ''
            );
        const defaultTitle =
            String(
                title.dataset.defaultTitle
                || title.textContent
                || ''
            );

        title.textContent =
            resolvedState === 'validated'
            && validatedTitle !== ''
                ? validatedTitle
                : (
                    resolvedState === 'processing'
                    && processingTitle !== ''
                        ? processingTitle
                        : defaultTitle
                );
    }

    if (message instanceof HTMLElement) {
        const validatedMessage =
            String(
                message.dataset.validatedMessage
                || ''
            );
        const processingMessage =
            String(
                message.dataset.processingMessage
                || ''
            );
        const defaultMessage =
            String(
                message.dataset.defaultMessage
                || message.textContent
                || ''
            );

        message.textContent =
            resolvedState === 'validated'
            && validatedMessage !== ''
                ? validatedMessage
                : (
                    resolvedState === 'processing'
                    && processingMessage !== ''
                        ? processingMessage
                        : defaultMessage
                );
    }

    splash.classList.add(
        'is-visible'
    );
    splash.removeAttribute(
        'aria-hidden'
    );
};

export const hidePaymentSplash = () => {
    const splash =
        getPaymentSplash();

    if (!(splash instanceof HTMLElement)) {
        return;
    }

    splash.classList.remove(
        'is-visible'
    );
    splash.setAttribute(
        'aria-hidden',
        'true'
    );
    delete splash.dataset.paymentState;
};

export const selectedPaymentMethod = form => {
    const input =
        form?.querySelector(
            'input[name="paymentmethod"]:checked'
        );

    return input
        ? String(
            input.value || ''
        ).toLowerCase()
        : '';
};
