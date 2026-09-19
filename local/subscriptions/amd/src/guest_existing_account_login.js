const initialise = form => {
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const password =
        form.querySelector(
            'input[name="password"]'
        );
    const submit =
        form.querySelector(
            'button[type="submit"]'
        );
    const feedback =
        form.querySelector(
            '[data-existing-account-login-feedback]'
        );

    if (!(password instanceof HTMLInputElement)) {
        return;
    }

    let submitting = false;

    const message = (
        text,
        state = ''
    ) => {
        if (!(feedback instanceof HTMLElement)) {
            return;
        }

        feedback.textContent =
            String(text || '');
        feedback.dataset.state =
            state;
        feedback.hidden =
            String(text || '') === '';
    };

    form.addEventListener(
        'submit',
        async event => {
            event.preventDefault();

            if (
                submitting
                || password.value === ''
            ) {
                return;
            }

            submitting = true;
            password.disabled = true;

            if (submit instanceof HTMLButtonElement) {
                submit.disabled = true;
            }

            message(
                form.dataset.loginWorking || '',
                'loading'
            );

            const body = new FormData();
            body.set(
                'password',
                password.value
            );
            body.set(
                'sesskey',
                form.dataset.sesskey || ''
            );

            try {
                const response =
                    await fetch(
                        form.dataset.loginUrl || '',
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

                const payload =
                    await response.json();

                if (
                    payload.ok
                    && payload.code === 'authenticated'
                    && payload.redirect
                ) {
                    window.location.assign(
                        payload.redirect
                    );
                    return;
                }

                password.value = '';
                message(
                    payload.code === 'invalid_credentials'
                        ? form.dataset.loginInvalid
                        : form.dataset.loginError,
                    'error'
                );
            } catch (error) {
                password.value = '';
                message(
                    form.dataset.loginError || '',
                    'error'
                );
            } finally {
                submitting = false;
                password.disabled = false;

                if (submit instanceof HTMLButtonElement) {
                    submit.disabled = false;
                }

                password.focus();
            }
        }
    );
};

export const init = () => {
    const run = () => {
        document.querySelectorAll(
            '[data-existing-account-inline-login]'
        ).forEach(initialise);
    };

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            run,
            {once: true}
        );
        return;
    }

    run();
};
