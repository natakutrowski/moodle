/** Guest checkout validation and post-payment account modal.
 * @module local_subscriptions/guest_checkout_security
 */

const validEmail = (input) => input.value.trim() !== '' && input.checkValidity();

const setGuestPaymentGate = (
    form,
    locked
) => {
    form.dataset.guestPaymentLocked =
        locked ? '1' : '0';

    form.classList.toggle(
        'is-guest-payment-locked',
        locked
    );

    form.querySelectorAll(
        '[data-guest-payment-control]'
    ).forEach(control => {
        control.classList.toggle(
            'is-guest-payment-locked',
            locked
        );
        control.setAttribute(
            'aria-disabled',
            locked ? 'true' : 'false'
        );

        if (control instanceof HTMLFieldSetElement) {
            control.disabled = locked;
        }
    });

    const message =
        form.querySelector(
            '[data-guest-payment-gate-message]'
        );

    if (message instanceof HTMLElement) {
        message.hidden = !locked;
    }

    form.dispatchEvent(
        new CustomEvent(
            'campusfr:guest-payment-gate-change',
            {
                bubbles: true,
                detail: {
                    locked,
                },
            }
        )
    );
};

const initialiseGuestPaymentGate = form => {
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const locked =
        form.dataset.guestPaymentLocked === '1';

    setGuestPaymentGate(
        form,
        locked
    );

    form.addEventListener(
        'click',
        event => {
            if (
                form.dataset.guestPaymentLocked !== '1'
            ) {
                return;
            }

            const target =
                event.target;

            if (
                target instanceof Element
                && target.closest(
                    '[data-payment-action-card], [data-payment-method], [data-quick-payment-action]'
                )
            ) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        },
        true
    );

    form.addEventListener(
        'keydown',
        event => {
            if (
                form.dataset.guestPaymentLocked !== '1'
            ) {
                return;
            }

            if (
                event.key !== 'Enter'
                && event.key !== ' '
            ) {
                return;
            }

            const target =
                event.target;

            if (
                target instanceof Element
                && target.closest(
                    '[data-payment-action-card], [data-payment-method], [data-quick-payment-action]'
                )
            ) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        },
        true
    );
};

const initialiseIdentityForm = () => {
    const form =
        document.querySelector(
            '[data-guest-checkout-form]'
        );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const identity =
        form.querySelector(
            '[data-checkout-guest-identity]'
        );
    const email =
        form.querySelector(
            '#guest-email'
        );
    const names = [
        ...form.querySelectorAll(
            '[data-identity-name]'
        ),
    ];
    const submit =
        form.querySelector(
            '[data-guest-checkout-submit]'
        );
    const terms =
        form.querySelector(
            '[data-checkout-terms]'
        );
    const feedback =
        form.querySelector(
            '#guest-email-feedback'
        );
    const otp =
        form.querySelector(
            '[data-guest-identity-otp]'
        );
    const otpCode =
        form.querySelector(
            '[data-guest-identity-otp-code]'
        );
    const otpStatus =
        form.querySelector(
            '[data-guest-identity-otp-status]'
        );
    const resend =
        form.querySelector(
            '[data-guest-identity-otp-resend]'
        );
    const verified =
        form.querySelector(
            '[data-guest-identity-verified]'
        );
    const confirmIdentity =
        form.querySelector(
            '[data-guest-identity-confirm]'
        );
    const confirmWrap =
        form.querySelector(
            '[data-guest-identity-confirm-wrap]'
        );

    if (
        !(identity instanceof HTMLElement)
        || !(email instanceof HTMLInputElement)
    ) {
        return;
    }

    let issuing = false;
    let verifying = false;
    let identityLocked = false;
    let lastIssuedSignature = '';
    let resendUntil = 0;
    let countdownTimer = null;

    const copy = key =>
        String(
            identity.dataset[key]
            || ''
        );

    const setOtpStatus = (
        message,
        state = ''
    ) => {
        if (!(otpStatus instanceof HTMLElement)) {
            return;
        }

        otpStatus.textContent =
            String(message || '');
        otpStatus.dataset.state =
            state;
    };

    const signature = () => [
        email.value.trim().toLowerCase(),
        ...names.map(item =>
            item instanceof HTMLInputElement
                ? item.value.trim()
                : ''
        ),
    ].join('|');

    const nameStates = () =>
        names.map(item => {
            if (!(item instanceof HTMLInputElement)) {
                return false;
            }

            const value =
                item.value.trim();
            const valid =
                value.length >= 2;
            const invalid =
                value.length === 1;

            item.classList.toggle(
                'is-valid',
                valid
            );
            item.classList.toggle(
                'is-invalid',
                invalid
            );
            item.setAttribute(
                'aria-invalid',
                invalid ? 'true' : 'false'
            );

            const status =
                item.parentElement?.querySelector(
                    '[data-identity-name-status]'
                );

            if (status) {
                status.textContent =
                    valid ? '✓' : (invalid ? '!' : '');
            }

            return valid;
        });

    const identityReadyForOtp = () =>
        validEmail(email)
        && nameStates().some(Boolean);

    const setResendCountdown = seconds => {
        resendUntil =
            Date.now()
            + Math.max(0, seconds) * 1000;

        if (!(resend instanceof HTMLButtonElement)) {
            return;
        }

        window.clearInterval(
            countdownTimer
        );

        const tick = () => {
            const remaining =
                Math.max(
                    0,
                    Math.ceil(
                        (resendUntil - Date.now())
                        / 1000
                    )
                );

            resend.disabled =
                remaining > 0;

            const baseLabel =
                resend.dataset.resendLabel
                || resend.textContent
                || '';

            resend.textContent =
                remaining > 0
                    ? baseLabel
                        + ' ('
                        + String(remaining)
                        + ' s)'
                    : baseLabel;

            if (remaining === 0) {
                window.clearInterval(
                    countdownTimer
                );
            }
        };

        tick();
        countdownTimer =
            window.setInterval(
                tick,
                1000
            );
    };

    const lockIdentity = () => {
        identityLocked = true;
        identity.classList.add(
            'is-identity-locked'
        );

        [email, ...names].forEach(input => {
            if (input instanceof HTMLInputElement) {
                input.readOnly = true;
                input.setAttribute(
                    'aria-readonly',
                    'true'
                );
            }
        });

        if (otp instanceof HTMLElement) {
            otp.hidden = true;
        }

        if (verified instanceof HTMLElement) {
            verified.hidden = false;
        }

        if (confirmWrap instanceof HTMLElement) {
            confirmWrap.hidden = true;
        }

        const emailStatus =
            email.parentElement?.querySelector(
                '.commerce-guest-checkout__status'
            );

        if (emailStatus) {
            emailStatus.textContent = '✓';
        }

        setGuestPaymentGate(
            form,
            false
        );
    };

    const post = async (
        url,
        values
    ) => {
        const body =
            new FormData();

        Object.entries(values).forEach(
            ([key, value]) => {
                body.set(
                    key,
                    String(value)
                );
            }
        );

        body.set(
            'sesskey',
            String(
                form.querySelector(
                    'input[name="sesskey"]'
                )?.value
                || ''
            )
        );

        const response =
            await fetch(
                url,
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

        let payload = null;

        try {
            payload =
                await response.json();
        } catch (error) {
            payload = null;
        }

        if (!payload) {
            throw new Error(
                'Invalid OTP response.'
            );
        }

        return payload;
    };

    const issueOtp = async (
        force = false
    ) => {
        if (
            identityLocked
            || issuing
            || !identityReadyForOtp()
        ) {
            return;
        }

        if (identity.dataset.personalOfferBearerProof === '1') {
            issuing = true;
            try {
                const payload = await post(
                    identity.dataset.personalOfferConfirmUrl || '',
                    {
                        firstname:
                            names[0] instanceof HTMLInputElement
                                ? names[0].value.trim()
                                : '',
                        lastname:
                            names[1] instanceof HTMLInputElement
                                ? names[1].value.trim()
                                : '',
                    }
                );

                if (payload.ok) {
                    window.location.reload();
                    return;
                }

                setOtpStatus(
                    copy('otpError'),
                    'error'
                );
            } catch (error) {
                setOtpStatus(
                    copy('otpError'),
                    'error'
                );
            } finally {
                issuing = false;
            }
            return;
        }

        const currentSignature =
            signature();

        if (
            !force
            && currentSignature === lastIssuedSignature
        ) {
            return;
        }

        issuing = true;

        // The OTP panel is also our customer-visible transport status area.
        // Show it before the request so AJAX/SMTP failures never look like a
        // dead button or an ignored blur event.
        if (otp instanceof HTMLElement) {
            otp.hidden = false;
        }

        setOtpStatus(
            copy('otpSending'),
            'loading'
        );

        try {
            const payload =
                await post(
                    copy('otpStartUrl'),
                    {
                        email:
                            email.value.trim(),
                        firstname:
                            names[0]?.value.trim()
                            || '',
                        lastname:
                            names[1]?.value.trim()
                            || '',
                    }
                );

            if (
                payload.code === 'sent'
                || payload.code === 'wait'
            ) {
                lastIssuedSignature =
                    currentSignature;

                if (otp instanceof HTMLElement) {
                    otp.hidden = false;
                }

                setOtpStatus(
                    copy('otpSent'),
                    'success'
                );
                setResendCountdown(
                    Number(
                        payload.retryAfter
                        || 0
                    )
                );

                if (
                    payload.code === 'sent'
                    && otpCode instanceof HTMLInputElement
                ) {
                    window.setTimeout(
                        () => otpCode.focus(),
                        0
                    );
                }

                return;
            }

            if (payload.code === 'delivery_failed') {
                setResendCountdown(0);
            }

            setOtpStatus(
                payload.code === 'rate_limited'
                    ? copy('otpLimited')
                    : copy('otpError'),
                'error'
            );
        } catch (error) {
            setOtpStatus(
                copy('otpError'),
                'error'
            );
        } finally {
            issuing = false;
        }
    };

    const verifyOtp = async () => {
        if (
            identityLocked
            || verifying
            || !(otpCode instanceof HTMLInputElement)
        ) {
            return;
        }

        const code =
            otpCode.value
                .replace(/\D/g, '')
                .slice(0, 6);

        otpCode.value = code;

        if (code.length !== 6) {
            return;
        }

        verifying = true;
        otpCode.disabled = true;
        setOtpStatus(
            copy('otpChecking'),
            'loading'
        );

        try {
            const payload =
                await post(
                    copy('otpVerifyUrl'),
                    {
                        code,
                    }
                );

            if (
                payload.ok
                && payload.code === 'verified'
            ) {
                setOtpStatus(
                    copy('otpVerified'),
                    'success'
                );
                lockIdentity();

                if (payload.requiresLogin) {
                    window.location.reload();
                }

                return;
            }

            if (
                payload.code === 'expired'
                || payload.code
                    === 'attempts_exhausted'
            ) {
                setOtpStatus(
                    payload.code === 'expired'
                        ? copy('otpExpired')
                        : copy('otpLimited'),
                    'error'
                );
                otpCode.value = '';
                return;
            }

            setOtpStatus(
                copy('otpInvalid'),
                'error'
            );
            otpCode.value = '';
        } catch (error) {
            setOtpStatus(
                copy('otpError'),
                'error'
            );
        } finally {
            verifying = false;

            if (
                !identityLocked
                && otpCode instanceof HTMLInputElement
            ) {
                otpCode.disabled = false;
                otpCode.focus();
            }
        }
    };

    const refresh = () => {
        const emailok =
            validEmail(email);
        const touched =
            email.value.trim() !== '';

        email.classList.toggle(
            'is-valid',
            emailok
        );
        email.classList.toggle(
            'is-invalid',
            touched && !emailok
        );
        email.setAttribute(
            'aria-invalid',
            touched && !emailok
                ? 'true'
                : 'false'
        );

        const status =
            email.parentElement?.querySelector(
                '.commerce-guest-checkout__status'
            );

        if (status) {
            status.textContent =
                emailok
                    ? '✓'
                    : (touched ? '!' : '');
        }

        if (feedback) {
            feedback.textContent =
                emailok
                    ? feedback.dataset.validLabel
                    : (
                        touched
                            ? feedback.dataset.invalidLabel
                            : ''
                    );
            feedback.classList.toggle(
                'is-valid',
                emailok
            );
            feedback.classList.toggle(
                'is-invalid',
                touched && !emailok
            );
        }

        const namesok =
            nameStates().some(Boolean);
        const termsok =
            !(terms instanceof HTMLInputElement)
            || terms.checked;
        const ready =
            emailok
            && namesok
            && termsok;

        if (submit instanceof HTMLButtonElement) {
            submit.disabled =
                !ready;
            submit.setAttribute(
                'aria-disabled',
                ready ? 'false' : 'true'
            );
        }

        if (!identityLocked) {
            if (confirmIdentity instanceof HTMLButtonElement) {
                confirmIdentity.disabled =
                    !(emailok && namesok);
            }

            if (!(emailok && namesok) && otp instanceof HTMLElement) {
                otp.hidden = true;
                lastIssuedSignature = '';
            }
        }
    };

    [email, ...names].forEach(input => {
        input.addEventListener(
            'input',
            refresh
        );
        input.addEventListener(
            'blur',
            event => {
                refresh();

                if (
                    identityLocked
                    || !identityReadyForOtp()
                ) {
                    return;
                }

                const next =
                    event.relatedTarget;

                // Let the customer naturally move between email / first name /
                // last name without firing a half-completed OTP. Validation is
                // automatic only when focus actually leaves the identity
                // fields; the explicit button remains available at any time.
                if (
                    next instanceof HTMLElement
                    && (
                        next.matches('#guest-email')
                        || next.matches('[data-identity-name]')
                    )
                ) {
                    return;
                }

                void issueOtp(false);
            }
        );
    });

    confirmIdentity?.addEventListener(
        'click',
        () => {
            void issueOtp(false);
        }
    );

    otpCode?.addEventListener(
        'input',
        () => {
            if (!(otpCode instanceof HTMLInputElement)) {
                return;
            }

            otpCode.value =
                otpCode.value
                    .replace(/\D/g, '')
                    .slice(0, 6);

            if (otpCode.value.length === 6) {
                void verifyOtp();
            }
        }
    );

    otpCode?.addEventListener(
        'paste',
        event => {
            if (!(otpCode instanceof HTMLInputElement)) {
                return;
            }

            const pasted =
                event.clipboardData
                    ?.getData('text')
                    .replace(/\D/g, '')
                    .slice(0, 6)
                || '';

            if (pasted.length !== 6) {
                return;
            }

            event.preventDefault();
            otpCode.value = pasted;
            void verifyOtp();
        }
    );

    resend?.addEventListener(
        'click',
        () => {
            if (
                Date.now()
                < resendUntil
            ) {
                return;
            }

            lastIssuedSignature = '';
            void issueOtp(true);
        }
    );

    terms?.addEventListener(
        'change',
        refresh
    );

    if (
        new URLSearchParams(
            window.location.search
        ).get('focus') === 'email'
    ) {
        window.setTimeout(
            () => email.focus(),
            0
        );
    }

    refresh();

    if (
        identity.dataset.personalOfferBearerProof === '1'
        && identityReadyForOtp()
    ) {
        void issueOtp(false);
    }
};

const initialisePersonalOfferIdentityCompletion = () => {
    const form = document.querySelector('[data-personal-offer-identity-completion]');
    if (!(form instanceof HTMLFormElement)) { return; }
    const button = form.querySelector('[data-personal-offer-identity-confirm]');
    const feedback = form.querySelector('[data-personal-offer-identity-feedback]');
    const firstname = form.querySelector('#personal-offer-firstname');
    const lastname = form.querySelector('#personal-offer-lastname');
    if (!(button instanceof HTMLButtonElement)) { return; }
    let working = false;
    const valid = input => !(input instanceof HTMLInputElement) || input.value.trim().length >= 2;
    const refresh = () => { button.disabled = working || !valid(firstname) || !valid(lastname); };
    [firstname, lastname].forEach(input => input?.addEventListener('input', refresh));
    button.addEventListener('click', async () => {
        if (working) { return; }
        working = true; refresh();
        if (feedback instanceof HTMLElement) { feedback.textContent = ''; }
        const body = new FormData();
        body.set('sesskey', String(form.querySelector('input[name="sesskey"]')?.value || ''));
        body.set('firstname', firstname instanceof HTMLInputElement ? firstname.value.trim() : '');
        body.set('lastname', lastname instanceof HTMLInputElement ? lastname.value.trim() : '');
        try {
            const response = await fetch(form.dataset.personalOfferConfirmUrl || '', {
                method: 'POST', body, credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
            });
            const payload = await response.json();
            if (!payload?.ok) { throw new Error('Personal Offer identity confirmation failed.'); }

            if (payload.requiresLogin) {
                window.location.reload();
                return;
            }

            if (payload.paymentReady) {
                const wrap = form.querySelector(
                    '[data-personal-offer-identity-completion-wrap]'
                );
                if (wrap instanceof HTMLElement) {
                    wrap.remove();
                }
                setGuestPaymentGate(form, false);
                return;
            }

            window.location.reload();
        } catch (error) {
            if (feedback instanceof HTMLElement) { feedback.textContent = 'Une erreur est survenue. Réessayez.'; }
            working = false; refresh();
        }
    });
    refresh();
};

const initialiseLegalConsent = () => {
    const form = document.querySelector('form[action*="commerce_checkout_action.php"]');
    if (!(form instanceof HTMLFormElement) || form.matches('[data-guest-checkout-form]')) {
        return;
    }
    const terms = form.querySelector('[data-checkout-terms]');
    const submit = form.querySelector('[data-checkout-submit]');
    if (!(terms instanceof HTMLInputElement) || !(submit instanceof HTMLButtonElement)) {
        return;
    }
    const refresh = () => {
        submit.disabled = !terms.checked;
        submit.setAttribute('aria-disabled', terms.checked ? 'false' : 'true');
    };
    terms.addEventListener('change', refresh);
    refresh();
};


const initialiseClickableLegalCard = () => {
    document.querySelectorAll(
        '[data-checkout-legal-card]'
    ).forEach(card => {
        const terms = card.querySelector(
            '[data-checkout-terms]'
        );

        if (!(terms instanceof HTMLInputElement)) {
            return;
        }

        const isInteractiveTarget = target => (
            target instanceof Element
            && target.closest(
                'a, input, label, button, select, textarea'
            ) !== null
        );

        const toggle = () => {
            if (
                terms.dataset.consentLocked === '1'
                || terms.checked
            ) {
                terms.checked = true;
                return;
            }

            terms.checked = true;
            terms.dispatchEvent(
                new Event('change', {
                    bubbles: true,
                })
            );
            terms.focus();
        };

        card.addEventListener('click', event => {
            if (isInteractiveTarget(event.target)) {
                return;
            }

            toggle();
        });

        card.addEventListener('keydown', event => {
            if (
                event.key !== 'Enter'
                && event.key !== ' '
            ) {
                return;
            }

            if (isInteractiveTarget(event.target)) {
                return;
            }

            event.preventDefault();
            toggle();
        });
    });
};


const initialiseAccountModal = () => {
    const dialog = document.querySelector('[data-guest-account-dialog]');
    if (!(dialog instanceof HTMLDialogElement)) {
        return;
    }
    const later = dialog.querySelector('[data-account-later]');
    const primary = dialog.querySelector('[data-account-primary]');
    const banner = document.querySelector('[data-account-reminder]');
    let returnFocus = null;
    const open = (trigger = null) => {
        returnFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
        if (!dialog.open) {
            dialog.showModal();
        }
        window.setTimeout(() => {
            if (primary instanceof HTMLElement) {
                primary.focus();
            }
        }, 0);
    };
    const close = () => {
        dialog.close();
        if (banner) {
            banner.hidden = false;
        }
        if (returnFocus instanceof HTMLElement && document.contains(returnFocus)) {
            returnFocus.focus();
        }
    };
    later?.addEventListener('click', close);
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        close();
    });
    document.querySelectorAll('[data-requires-account-finalisation]').forEach((control) => {
        control.addEventListener('click', (event) => {
            event.preventDefault();
            open(control);
        });
    });
    const autoOpen = dialog.dataset.accountDialogAutoOpen !== '0';
    if (autoOpen && !dialog.open) {
        open();
    }
};

export const init = () => {
    const run = () => {
        document.querySelectorAll(
            '[data-checkout-payment-form]'
        ).forEach(initialiseGuestPaymentGate);
        initialiseIdentityForm();
        initialisePersonalOfferIdentityCompletion();
        initialiseLegalConsent();
        initialiseClickableLegalCard();
        initialiseAccountModal();
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, {once: true});
    } else {
        run();
    }
};