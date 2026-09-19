// Commerce 7.96H10.9 — Alfa Payment Widget orchestration and UX stabilisation.

const BUILD_ID = '7.96H10.10';

const selectedMethod = form => {
    const selected =
        form.querySelector(
            'input[name="paymentmethod"]:checked'
        );

    return selected
        ? String(selected.value || '').toLowerCase()
        : '';
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
    }
};

const setError = (node, message) => {
    if (!node) {
        return;
    }

    node.textContent = String(message || '');
    node.hidden = !message;
};

const resolveWidgetLanguage = language =>
    String(language || '')
        .trim()
        .toLowerCase()
        .startsWith('ru')
        ? 'ru'
        : 'en';

const isPassiveCancellationMessage = message => {
    const normalised =
        String(message || '')
            .trim()
            .toLowerCase();

    return [
        'заказ не оплачен',
        'order not paid',
    ].includes(normalised);
};

const localiseOfficialButton = (
    button,
    label,
    log
) => {
    const text = String(label || '').trim();

    if (text === '') {
        return;
    }

    button.textContent = text;
    button.setAttribute('aria-label', text);

    log(
        'Official button localised',
        {label: text}
    );
};

const guardOfficialMessage = (mount, log) => {
    const apply = () => {
        const message =
            mount.querySelector(
                '#alfa-payment__message'
            );

        if (!(message instanceof HTMLElement)) {
            return;
        }

        const text =
            String(message.textContent || '')
                .trim();

        if (!isPassiveCancellationMessage(text)) {
            message.hidden = false;
            message.removeAttribute('aria-hidden');
            return;
        }

        message.hidden = true;
        message.setAttribute('aria-hidden', 'true');

        log(
            'Passive Alfa cancellation message suppressed',
            {message: text}
        );
    };

    const observer =
        new MutationObserver(apply);

    observer.observe(
        mount,
        {
            childList: true,
            subtree: true,
            characterData: true,
        }
    );

    apply();

    return observer;
};

const sanitise = value => {
    if (Array.isArray(value)) {
        return value.map(sanitise);
    }

    if (
        value === null
        || typeof value !== 'object'
    ) {
        return value;
    }

    const result = {};

    Object.entries(value).forEach(
        ([key, item]) => {
            if (
                key.toLowerCase().includes('token')
                && key !== 'token_present'
                && key !== 'token_length'
            ) {
                const text = String(item || '');

                result[`${key}_present`] =
                    text !== '';
                result[`${key}_length`] =
                    text.length;
                return;
            }

            result[key] = sanitise(item);
        }
    );

    return result;
};

const domSnapshot = mount => {
    const script =
        document.querySelector(
            '#alfa-payment-script'
        );
    const widget =
        document.querySelector(
            '#alfa-payment-button'
        );
    const official =
        document.querySelector(
            '#alfa-payment__button'
        );
    const style =
        document.querySelector(
            '#alfa-payment-style'
        );
    const modal =
        document.querySelector(
            '#alfa-payment'
        );

    return {
        documentReadyState:
            document.readyState,
        scriptPresent:
            script instanceof HTMLScriptElement,
        scriptSrc:
            script instanceof HTMLScriptElement
                ? script.src
                : '',
        scriptInitType:
            script
                ? typeof script.init
                : 'missing',
        mountConnected:
            Boolean(mount?.isConnected),
        mountChildCount:
            mount?.childElementCount ?? -1,
        mountHtmlLength:
            mount?.innerHTML?.length ?? -1,
        widgetPresent:
            widget instanceof HTMLElement,
        widgetConnected:
            Boolean(widget?.isConnected),
        officialButtonPresent:
            official instanceof HTMLButtonElement,
        officialButtonInMount:
            Boolean(
                official
                && mount
                && mount.contains(official)
            ),
        stylePresent:
            style instanceof HTMLLinkElement,
        styleHref:
            style instanceof HTMLLinkElement
                ? style.href
                : '',
        modalPresent:
            modal instanceof HTMLElement,
        alfaIds:
            Array.from(
                document.querySelectorAll(
                    '[id^="alfa-payment"]'
                )
            ).map(node => node.id),
    };
};

const createDebugger = (
    config,
    mount
) => {
    const enabled =
        Boolean(config.debug);

    // Production stays completely silent: no console trace and no global
    // diagnostic state exposed to checkout visitors.
    if (!enabled) {
        return () => {};
    }

    const state = {
        build: BUILD_ID,
        server: sanitise(
            config.serverDiagnostics || {}
        ),
        client: {
            href: window.location.href,
            protocol: window.location.protocol,
            hostname: window.location.hostname,
            userAgent: navigator.userAgent,
            language: navigator.language,
            platform: navigator.platform,
        },
        steps: [],
    };

    const log = (
        step,
        data = {}
    ) => {
        state.steps.push({
            time:
                new Date().toISOString(),
            step,
            data: sanitise(data),
        });
    };

    // Headless maintenance hook, available only under DEBUG_DEVELOPER.
    window.__campusAlfaWidgetDebug =
        state;

    log(
        'AMD init',
        {
            debug: enabled,
            dom:
                domSnapshot(mount),
        }
    );

    return log;
};

const prepare = async (
    form,
    config,
    log
) => {
    const body = new FormData(form);
    body.set('paymentmethod', 'card');
    body.set('ajax', '1');
    body.set(
        'checkoutlanguage',
        String(config.checkoutLanguage || '')
    );

    log(
        'Prepare request starting',
        {
            actionUrl:
                config.actionUrl,
            paymentmethod:
                body.get('paymentmethod'),
            ajax:
                body.get('ajax'),
        }
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

    log(
        'Prepare response received',
        {
            status: response.status,
            ok: response.ok,
            contentType:
                response.headers.get(
                    'content-type'
                ),
        }
    );

    let payload;

    try {
        payload = await response.json();
    } catch (error) {
        log(
            'Prepare response JSON failed',
            {
                error:
                    String(
                        error?.message
                        || error
                    ),
            }
        );
        throw new Error(
            config.prepareError
        );
    }

    log(
        'Prepare payload decoded',
        payload
    );

    if (
        !response.ok
        || !payload.ok
        || !['alfa_widget', 'alfa_iframe'].includes(payload.type)
        || !payload.parameters
    ) {
        log(
            'Prepare payload rejected',
            {
                responseOk:
                    response.ok,
                payloadOk:
                    payload?.ok,
                type:
                    payload?.type,
                hasParameters:
                    Boolean(
                        payload?.parameters
                    ),
                error:
                    payload?.error || '',
            }
        );

        throw new Error(
            payload?.error
            || config.prepareError
        );
    }

    return payload;
};

const wakeOfficialWidget = log => {
    const script =
        document.querySelector(
            '#alfa-payment-script'
        );

    log(
        'Official widget wake requested',
        {
            scriptPresent:
                script instanceof
                    HTMLScriptElement,
            scriptSrc:
                script instanceof
                    HTMLScriptElement
                    ? script.src
                    : '',
            initType:
                script
                    ? typeof script.init
                    : 'missing',
        }
    );

    if (
        !(script instanceof
            HTMLScriptElement)
    ) {
        log(
            'Official widget wake skipped',
            {
                reason:
                    'script element missing',
            }
        );
        return false;
    }

    if (
        typeof script.init
        !== 'function'
    ) {
        log(
            'Official widget wake skipped',
            {
                reason:
                    'script.init is not a function',
            }
        );
        return false;
    }

    try {
        script.init();

        log(
            'Official widget init called'
        );
        return true;
    } catch (error) {
        log(
            'Official widget init threw',
            {
                name:
                    error?.name || '',
                message:
                    String(
                        error?.message
                        || error
                    ),
                stack:
                    error?.stack || '',
            }
        );
        throw error;
    }
};

const waitForOfficialButton = (
    mount,
    log,
    timeout = 7000
) =>
    new Promise((resolve, reject) => {
        const started = Date.now();
        let lastSecond = -1;

        const probe = () => {
            const official =
                document.querySelector(
                    '#alfa-payment__button'
                );
            const elapsed =
                Date.now() - started;
            const second =
                Math.floor(
                    elapsed / 1000
                );

            if (
                second !== lastSecond
            ) {
                lastSecond = second;

                log(
                    'Waiting for official button',
                    {
                        elapsedMs:
                            elapsed,
                        dom:
                            domSnapshot(
                                mount
                            ),
                    }
                );
            }

            if (
                official instanceof
                    HTMLButtonElement
            ) {
                log(
                    'Official button ready',
                    {
                        elapsedMs:
                            elapsed,
                        text:
                            official.innerText,
                        className:
                            official.className,
                        dom:
                            domSnapshot(
                                mount
                            ),
                    }
                );

                resolve(official);
                return;
            }

            if (
                elapsed >= timeout
            ) {
                log(
                    'Official button timeout',
                    {
                        elapsedMs:
                            elapsed,
                        dom:
                            domSnapshot(
                                mount
                            ),
                    }
                );

                reject(
                    new Error(
                        'Alfa widget did not initialise.'
                    )
                );
                return;
            }

            window.setTimeout(
                probe,
                100
            );
        };

        probe();
    });

const buildWidget = (
    mount,
    parameters,
    log
) => {
    mount.replaceChildren();

    const order =
        document.createElement('span');
    order.id =
        'commerce-alfa-widget-order-number';
    order.hidden = true;
    order.textContent =
        String(
            parameters.order_number
            || ''
        );
    mount.appendChild(order);

    const description =
        document.createElement('span');
    description.id =
        'commerce-alfa-widget-description';
    description.hidden = true;
    description.textContent =
        String(
            parameters.description
            || ''
        );
    mount.appendChild(description);

    const widget =
        document.createElement('div');

    widget.id =
        'alfa-payment-button';
    widget.dataset.token =
        String(
            parameters.token
            || ''
        );
    widget.dataset.gateway =
        String(
            parameters.gateway
            || ''
        );
    widget.dataset.amount =
        String(
            parameters.amount_minor
            || ''
        );
    widget.dataset.version =
        String(
            parameters.version
            || '1.0'
        );
    widget.dataset.orderNumberSelector =
        '#commerce-alfa-widget-order-number';
    widget.dataset.language =
        String(
            parameters.language
            || 'ru'
        );
    widget.dataset.stages =
        String(
            parameters.stages
            || '1'
        );
    widget.dataset.returnUrl =
        String(
            parameters.return_url
            || ''
        );
    widget.dataset.failUrl =
        String(
            parameters.fail_url
            || ''
        );
    widget.dataset.amountFormat =
        String(
            parameters.amount_format
            || 'kopeyki'
        );
    widget.dataset.descriptionSelector =
        '#commerce-alfa-widget-description';

    mount.appendChild(widget);

    log(
        'Widget mount created',
        {
            parameters,
            dataset: {
                tokenPresent:
                    widget.dataset.token
                    !== '',
                tokenLength:
                    widget.dataset.token
                        .length,
                gateway:
                    widget.dataset.gateway,
                amount:
                    widget.dataset.amount,
                version:
                    widget.dataset.version,
                orderNumberSelector:
                    widget.dataset
                        .orderNumberSelector,
                language:
                    widget.dataset.language,
                stages:
                    widget.dataset.stages,
                returnUrl:
                    widget.dataset.returnUrl,
                failUrl:
                    widget.dataset.failUrl,
                amountFormat:
                    widget.dataset
                        .amountFormat,
                descriptionSelector:
                    widget.dataset
                        .descriptionSelector,
            },
            dom:
                domSnapshot(mount),
        }
    );

    return widget;
};


export const init = () => {
    const form =
        document.querySelector(
            '[data-checkout-payment-form]'
        );
    const panel =
        document.querySelector(
            '[data-checkout-alfa-widget]'
        );
    const iframePanel =
        document.querySelector(
            '[data-checkout-alfa-iframe]'
        );
    const iframeFrame =
        iframePanel?.querySelector(
            '[data-checkout-alfa-iframe-frame]'
        );
    const iframeFallback =
        iframePanel?.querySelector(
            '[data-checkout-alfa-iframe-fallback]'
        );
    const iframeLoading =
        iframePanel?.querySelector(
            '[data-checkout-alfa-iframe-loading]'
        );
    const mount =
        document.querySelector(
            '[data-checkout-alfa-widget-mount]'
        );
    const errorbox =
        document.querySelector(
            '[data-checkout-alfa-widget-error]'
        );
    const submit =
        form?.querySelector(
            '[data-checkout-submit]'
        );

    let serverDiagnostics = {};
    if (panel instanceof HTMLElement) {
        try {
            serverDiagnostics = JSON.parse(
                panel.dataset.serverDiagnostics || '{}'
            );
        } catch (error) {
            serverDiagnostics = {};
        }
    }

    const config = panel instanceof HTMLElement
        ? {
            actionUrl: panel.dataset.actionUrl || '',
            prepareError: panel.dataset.prepareError || '',
            openLabel: panel.dataset.openLabel || '',
            readyLabel: panel.dataset.readyLabel || '',
            defaultLabel: panel.dataset.defaultLabel || '',
            cardLabel: panel.dataset.cardLabel || '',
            checkoutLanguage:
                panel.dataset.checkoutLanguage || '',
            debug: panel.dataset.debug === '1',
            serverDiagnostics,
        }
        : {};

    if (
        !(form instanceof HTMLFormElement)
        || !(panel instanceof HTMLElement)
        || !(mount instanceof HTMLElement)
        || !(submit instanceof HTMLButtonElement)
    ) {
        return;
    }

    const showIframeLoading = () => {
        if (!(iframePanel instanceof HTMLElement)) {
            return;
        }

        iframePanel.hidden = false;

        if (iframeLoading instanceof HTMLElement) {
            iframeLoading.hidden = false;
        }

        const frameWrap = iframeFrame?.closest(
            '.commerce-checkout-alfa-iframe__frame-wrap'
        );

        if (frameWrap instanceof HTMLElement) {
            frameWrap.hidden = true;
        }

        if (iframeFallback instanceof HTMLAnchorElement) {
            iframeFallback.hidden = true;
        }
    };

    const hideIframeLoading = () => {
        if (iframeLoading instanceof HTMLElement) {
            iframeLoading.hidden = true;
        }

        const frameWrap = iframeFrame?.closest(
            '.commerce-checkout-alfa-iframe__frame-wrap'
        );

        if (frameWrap instanceof HTMLElement) {
            frameWrap.hidden = false;
        }
    };


    const log =
        createDebugger(
            config,
            mount
        );



    const script =
        document.querySelector(
            '#alfa-payment-script'
        );

    if (
        script instanceof
            HTMLScriptElement
    ) {
        script.addEventListener(
            'load',
            () => log(
                'Official script load event',
                {
                    dom:
                        domSnapshot(mount),
                }
            )
        );

        script.addEventListener(
            'error',
            event => log(
                'Official script error event',
                {
                    type:
                        event.type,
                    dom:
                        domSnapshot(mount),
                }
            )
        );
    }

    window.setTimeout(
        () => log(
            '1s initial probe',
            {
                dom:
                    domSnapshot(mount),
            }
        ),
        1000
    );

    let prepared = false;
    let preparing = false;
    let iframeMounted = false;

    const hasMountedIframe = () => (
        iframeMounted
        && iframeFrame instanceof HTMLIFrameElement
        && iframeFrame.src !== ''
        && iframeFrame.src !== 'about:blank'
    );

    const hideAlfaCardSurface = () => {
        if (iframePanel instanceof HTMLElement) {
            iframePanel.hidden = true;
        }

        if (panel instanceof HTMLElement) {
            panel.hidden = true;
        }
    };

    const showPreparedCardSurface = () => {
        if (!hasMountedIframe()) {
            return false;
        }

        if (iframePanel instanceof HTMLElement) {
            iframePanel.hidden = false;
        }

        if (panel instanceof HTMLElement) {
            panel.hidden = true;
        }

        return true;
    };

    const syncPaymentSurface = () => {
        const method = selectedMethod(form);

        if (method !== 'card') {
            hideAlfaCardSurface();
            return;
        }

        if (showPreparedCardSurface()) {
            return;
        }

        if (
            preparing
            && iframePanel instanceof HTMLElement
        ) {
            showIframeLoading();
        }
    };

    // H12.4.1: selecting Card only chooses the payment method.
    // The primary checkout CTA remains the explicit action that launches
    // the Alfa widget after identity/terms validation.
    const refreshSubmit = () => {
        const cardselected =
            selectedMethod(form)
            === 'card';

        if (!cardselected) {
            submit.hidden = true;
            submit.disabled = false;
            setSubmitLabel(
                submit,
                config.defaultLabel
            );
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
            config.openLabel
        );
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
                method === 'card'
                && iframeMounted
                && showPreparedCardSurface()
            ) {
                event.preventDefault();
                refreshSubmit();

                iframePanel?.scrollIntoView({
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
                const method =
                    selectedMethod(
                        form
                    );

                log(
                    'Payment method changed',
                    {
                        selected:
                            method,
                        iframeMounted,
                        preparing,
                    }
                );

                syncPaymentSurface();
                refreshSubmit();
            }
        );
    });

    document.querySelectorAll(
        '[data-quick-payment-action]'
    ).forEach(button => {
        button.addEventListener(
            'click',
            () => {
                hideAlfaCardSurface();
            },
            true
        );
    });

    form.addEventListener(
        'submit',
        async event => {
            if (
                selectedMethod(form)
                !== 'card'
            ) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            log(
                'Alfa submit intercepted',
                {
                    prepared,
                    preparing,
                    formValid:
                        typeof form
                            .checkValidity
                            === 'function'
                            ? form
                                .checkValidity()
                            : null,
                    dom:
                        domSnapshot(
                            mount
                        ),
                }
            );

            if (
                typeof form
                    .checkValidity
                    === 'function'
                && !form.checkValidity()
            ) {
                form.reportValidity?.();
                log(
                    'Submit stopped by HTML validation'
                );
                return;
            }

            if (
                prepared
                || preparing
            ) {
                log(
                    'Submit ignored because state is busy',
                    {
                        prepared,
                        preparing,
                    }
                );
                panel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
                return;
            }

            preparing = true;
            refreshSubmit();
            setError(errorbox, '');

            if (iframePanel instanceof HTMLElement) {
                showIframeLoading();
            }

            try {
                const preparedPayload =
                    await prepare(
                        form,
                        config,
                        log
                    );

                if (
                    preparedPayload.type === 'alfa_iframe'
                ) {
                    const formUrl =
                        String(
                            preparedPayload.parameters.form_url
                            || ''
                        ).trim();

                    if (
                        formUrl === ''
                        || !(iframePanel instanceof HTMLElement)
                        || !(iframeFrame instanceof HTMLIFrameElement)
                    ) {
                        throw new Error(
                            config.prepareError
                        );
                    }

                    panel.hidden = true;
                    iframeFrame.src = formUrl;
                    iframeMounted = true;
                    hideIframeLoading();

                    if (
                        iframeFallback instanceof HTMLAnchorElement
                    ) {
                        iframeFallback.href = formUrl;
                        iframeFallback.hidden = false;
                    }

                    preparing = false;
                    prepared = true;

                    const cardStillSelected =
                        selectedMethod(form) === 'card';

                    iframePanel.hidden =
                        !cardStillSelected;

                    refreshSubmit();

                    if (cardStillSelected) {
                        iframePanel.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                        });
                    }

                    log(
                        'Hosted Alfa Payment Page mounted in iframe',
                        {
                            host:
                                (() => {
                                    try {
                                        return new URL(formUrl).hostname;
                                    } catch (error) {
                                        return '';
                                    }
                                })(),
                        }
                    );

                    return;
                }

                const parameters =
                    preparedPayload.parameters;

                parameters.language =
                    resolveWidgetLanguage(
                        config.checkoutLanguage
                    );

                log(
                    'Widget language resolved from checkout locale',
                    {
                        checkoutLanguage:
                            config.checkoutLanguage || '',
                        widgetLanguage:
                            parameters.language,
                    }
                );

                buildWidget(
                    mount,
                    parameters,
                    log
                );

                panel.hidden = false;

                const woke =
                    wakeOfficialWidget(
                        log
                    );

                log(
                    'Wake result',
                    {
                        woke,
                        dom:
                            domSnapshot(
                                mount
                            ),
                    }
                );

                const officialButton =
                    await waitForOfficialButton(
                        mount,
                        log
                    );

                localiseOfficialButton(
                    officialButton,
                    config.cardLabel,
                    log
                );
                guardOfficialMessage(
                    mount,
                    log
                );

                prepared = true;
                refreshSubmit();

                log(
                    'Opening official Alfa modal directly'
                );
                officialButton.click();

                window.setTimeout(
                    () => log(
                        'Direct modal open probe',
                        {
                            dom:
                                domSnapshot(
                                    mount
                                ),
                        }
                    ),
                    250
                );
            } catch (error) {
                log(
                    'Alfa flow failed',
                    {
                        name:
                            error?.name || '',
                        message:
                            String(
                                error?.message
                                || error
                            ),
                        stack:
                            error?.stack || '',
                        dom:
                            domSnapshot(
                                mount
                            ),
                    }
                );

                if (iframePanel instanceof HTMLElement) {
                    iframePanel.hidden = true;
                    hideIframeLoading();
                }

                panel.hidden = false;
                setError(
                    errorbox,
                    error?.message ===
                        'Alfa widget did not initialise.'
                        ? config.prepareError
                        : (
                            error?.message
                            || config.prepareError
                        )
                );
            } finally {
                preparing = false;
                syncPaymentSurface();
                refreshSubmit();

                log(
                    'Alfa flow settled',
                    {
                        prepared,
                        preparing,
                        dom:
                            domSnapshot(
                                mount
                            ),
                    }
                );
            }
        },
        true
    );

    syncPaymentSurface();
    refreshSubmit();
};
