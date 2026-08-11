/** Guest checkout validation and post-payment account modal.
 * @module local_subscriptions/guest_checkout_security
 */

const validEmail = (input) => input.value.trim() !== '' && input.checkValidity();

const initialiseIdentityForm = () => {
    const form = document.querySelector('[data-guest-checkout-form]');
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    const email = form.querySelector('#guest-email');
    const names = [...form.querySelectorAll('[data-required-name]')];
    const submit = form.querySelector('[data-guest-checkout-submit]');
    const terms = form.querySelector('[data-checkout-terms]');
    const feedback = form.querySelector('#guest-email-feedback');
    if (!(email instanceof HTMLInputElement) || !(submit instanceof HTMLButtonElement)) {
        return;
    }
    const refresh = () => {
        const emailok = validEmail(email);
        const touched = email.value.trim() !== '';
        email.classList.toggle('is-valid', emailok);
        email.classList.toggle('is-invalid', touched && !emailok);
        email.setAttribute('aria-invalid', touched && !emailok ? 'true' : 'false');
        const status = email.parentElement?.querySelector('.commerce-guest-checkout__status');
        if (status) {
            status.textContent = emailok ? '✓' : (touched ? '!' : '');
        }
        if (feedback) {
            feedback.textContent = emailok ? feedback.dataset.validLabel : (touched ? feedback.dataset.invalidLabel : '');
            feedback.classList.toggle('is-valid', emailok);
            feedback.classList.toggle('is-invalid', touched && !emailok);
        }
        const namesok = names.every((item) => item instanceof HTMLInputElement && item.value.trim() !== '');
        const termsok = !(terms instanceof HTMLInputElement) || terms.checked;
        const ready = emailok && namesok && termsok;
        submit.disabled = !ready;
        submit.setAttribute('aria-disabled', ready ? 'false' : 'true');
    };
    [email, ...names].forEach((input) => {
        input.addEventListener('input', refresh);
        input.addEventListener('blur', refresh);
    });
    terms?.addEventListener('change', refresh);
    if (new URLSearchParams(window.location.search).get('focus') === 'email') {
        window.setTimeout(() => email.focus(), 0);
    }
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
        initialiseIdentityForm();
        initialiseLegalConsent();
        initialiseAccountModal();
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, {once: true});
    } else {
        run();
    }
};