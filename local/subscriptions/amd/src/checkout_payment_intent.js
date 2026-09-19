// Commerce 7.96H12.7.3 — payment intent + legal consent state machine.

const FORM = '[data-checkout-payment-form]';
const ACTION_CARD = '[data-payment-action-card]';
const TERMS = '[data-checkout-terms]';
const LEGAL_CARD = '[data-checkout-legal-card]';
const SUBMIT = '[data-checkout-submit]';
const TERMS_FEEDBACK = '[data-checkout-terms-feedback]';
const LEGAL_CHECK = '[data-checkout-legal-check]';

const radioFor = (
    form,
    method
) => form.querySelector(
    'input[name="paymentmethod"][value="' + CSS.escape(method) + '"]'
);

const selectedMethod = form => {
    const radio = form.querySelector(
        'input[name="paymentmethod"]:checked'
    );

    return radio
        ? String(radio.value || '').toLowerCase()
        : '';
};

const syncCards = form => {
    form.querySelectorAll(
        ACTION_CARD
    ).forEach(card => {
        const method =
            String(
                card.dataset.paymentActionCard
                || ''
            ).toLowerCase();
        const radio =
            method !== ''
                ? radioFor(
                    form,
                    method
                )
                : null;
        const selected =
            radio instanceof HTMLInputElement
            && radio.checked;

        card.classList.toggle(
            'is-selected',
            selected
        );
        card.setAttribute(
            'aria-pressed',
            selected
                ? 'true'
                : 'false'
        );
    });
};

const selectMethod = (
    form,
    method
) => {
    const radio =
        radioFor(
            form,
            method
        );

    if (!(radio instanceof HTMLInputElement)) {
        return false;
    }

    if (!radio.checked) {
        radio.checked = true;
        radio.dispatchEvent(
            new Event(
                'change',
                {
                    bubbles: true,
                }
            )
        );
    }

    syncCards(form);

    return true;
};

const submitIntent = (
    form,
    submit
) => {
    if (
        typeof form.requestSubmit
        === 'function'
    ) {
        form.requestSubmit(
            submit instanceof HTMLElement
                ? submit
                : undefined
        );
        return;
    }

    form.dispatchEvent(
        new Event(
            'submit',
            {
                bubbles: true,
                cancelable: true,
            }
        )
    );
};

export const init = () => {
    document.querySelectorAll(
        FORM
    ).forEach(form => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const terms =
            form.querySelector(
                TERMS
            );
        const legalCard =
            form.querySelector(
                LEGAL_CARD
            );
        const submit =
            form.querySelector(
                SUBMIT
            );

        const termsFeedback =
            form.querySelector(
                TERMS_FEEDBACK
            );
        const legalCheck =
            form.querySelector(
                LEGAL_CHECK
            );

        if (
            !(terms instanceof HTMLInputElement)
            || !(legalCard instanceof HTMLElement)
        ) {
            return;
        }

        let pendingMethod = '';
        let consentLocked =
            terms.checked;

        if (submit instanceof HTMLElement) {
            submit.hidden =
                selectedMethod(form) === '';
        }

        const revealSubmitForIntent = () => {
            if (submit instanceof HTMLElement) {
                submit.hidden = false;
            }
        };

        const hideConsentFeedback = () => {
            if (termsFeedback instanceof HTMLElement) {
                termsFeedback.hidden = true;
            }
        };

        const showConsentFeedback = () => {
            if (termsFeedback instanceof HTMLElement) {
                termsFeedback.hidden = false;
            }
        };

        const markConsentAccepted = () => {
            consentLocked = true;
            terms.checked = true;
            terms.dataset.consentLocked =
                '1';
            terms.setAttribute(
                'aria-disabled',
                'true'
            );

            legalCard.classList.remove(
                'is-required'
            );
            legalCard.classList.add(
                'is-accepted'
            );
            legalCard.dataset.consentState =
                'accepted';

            hideConsentFeedback();

            legalCard.setAttribute(
                'aria-checked',
                'true'
            );

            if (legalCheck instanceof HTMLElement) {
                legalCheck.hidden = false;
            }

            form.dispatchEvent(
                new CustomEvent(
                    'campusfr:checkout-consent-accepted',
                    {
                        bubbles: true,
                    }
                )
            );
        };

        const requestConsent = method => {
            pendingMethod = method;

            if (submit instanceof HTMLElement) {
                submit.hidden = true;
            }

            legalCard.classList.add(
                'is-required'
            );
            legalCard.classList.remove(
                'is-accepted'
            );
            legalCard.dataset.consentState =
                'required';
            legalCard.setAttribute(
                'aria-checked',
                'false'
            );

            showConsentFeedback();

            legalCard.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
            });

            window.setTimeout(
                () => terms.focus(),
                180
            );
        };

        const execute = method => {
            pendingMethod = '';

            if (
                method === ''
                || selectedMethod(form)
                    !== method
            ) {
                return;
            }

            const resumeEvent =
                new CustomEvent(
                    'campusfr:payment-intent-execute',
                    {
                        bubbles: true,
                        cancelable: true,
                        detail: {
                            method,
                        },
                    }
                );

            // Prepared provider surfaces may consume the intent simply by
            // revealing/reusing themselves. In that case no new submit should
            // be generated and, crucially, no confirmation should be fired.
            if (!form.dispatchEvent(resumeEvent)) {
                return;
            }

            revealSubmitForIntent();

            submitIntent(
                form,
                submit
            );
        };

        const choose = method => {
            if (
                method === ''
                || !selectMethod(
                    form,
                    method
                )
            ) {
                return;
            }

            if (!terms.checked) {
                requestConsent(
                    method
                );
                return;
            }

            if (!consentLocked) {
                markConsentAccepted();
            }

            execute(
                method
            );
        };

        form.addEventListener(
            'campusfr:checkout-consent-required',
            () => {
                if (!terms.checked) {
                    requestConsent(
                        pendingMethod
                    );
                }
            }
        );

        // Own every Action Card before provider-specific target handlers.
        // This gives all rails the exact same "choose -> consent -> execute"
        // contract while existing provider submit interceptors remain intact.
        form.addEventListener(
            'click',
            event => {
                const target =
                    event.target instanceof Element
                        ? event.target
                        : null;
                const card =
                    target?.closest(
                        ACTION_CARD
                    );

                if (
                    !(card instanceof HTMLElement)
                    || !form.contains(card)
                ) {
                    return;
                }

                const method =
                    String(
                        card.dataset.paymentActionCard
                        || ''
                    ).toLowerCase();

                event.preventDefault();
                event.stopPropagation();

                choose(
                    method
                );
            },
            true
        );

        form.addEventListener(
            'keydown',
            event => {
                if (
                    event.key !== 'Enter'
                    && event.key !== ' '
                ) {
                    return;
                }

                const target =
                    event.target instanceof Element
                        ? event.target
                        : null;
                const card =
                    target?.closest(
                        ACTION_CARD
                    );

                if (
                    !(card instanceof HTMLElement)
                    || !form.contains(card)
                    || card instanceof HTMLButtonElement
                ) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                choose(
                    String(
                        card.dataset.paymentActionCard
                        || ''
                    ).toLowerCase()
                );
            },
            true
        );

        // Consent is a one-way action during this checkout. Keep the input
        // enabled so its value continues to be posted by normal form submits.
        terms.addEventListener(
            'click',
            event => {
                if (!consentLocked) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                terms.checked = true;
            },
            true
        );

        terms.addEventListener(
            'change',
            () => {
                if (
                    consentLocked
                    && !terms.checked
                ) {
                    terms.checked = true;
                    return;
                }

                if (!terms.checked) {
                    return;
                }

                markConsentAccepted();

                if (pendingMethod !== '') {
                    execute(
                        pendingMethod
                    );
                }
            }
        );

        if (terms.checked) {
            markConsentAccepted();
        } else {
            legalCard.dataset.consentState =
                'idle';
            legalCard.setAttribute(
                'aria-checked',
                'false'
            );
        }

        // H12.7.3 invariant: no default provider selection. An explicit URL
        // paymentmethod remains an intentional selection and is preserved.
        syncCards(
            form
        );
    });
};
