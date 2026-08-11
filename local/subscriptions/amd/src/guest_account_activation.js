/**
 * Interactive validation for paid Guest Checkout account activation.
 *
 * @module local_subscriptions/guest_account_activation
 */

const updateVisibilityButton = (button, visible) => {
    const icon = button.querySelector('i');
    const label = visible ? button.dataset.hideLabel : button.dataset.showLabel;
    if (icon) {
        icon.classList.toggle('fa-eye', !visible);
        icon.classList.toggle('fa-eye-slash', visible);
    }
    button.setAttribute('aria-pressed', visible ? 'true' : 'false');
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
};

const enhanceInput = (input, settings) => {
    if (input.dataset.visibilityToggleInitialised === '1') {
        return;
    }
    const parent = input.parentElement;
    if (!parent) {
        return;
    }
    input.dataset.visibilityToggleInitialised = '1';
    const control = document.createElement('div');
    control.className = 'commerce-guest-activation__password-control';
    parent.insertBefore(control, input);
    control.appendChild(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'commerce-guest-activation__password-toggle';
    button.dataset.showLabel = settings.showLabel;
    button.dataset.hideLabel = settings.hideLabel;
    button.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';
    updateVisibilityButton(button, false);
    button.addEventListener('click', (event) => {
        event.preventDefault();
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        updateVisibilityButton(button, visible);
        input.focus();
    });
    control.appendChild(button);
};

const countMatches = (value, expression) => (value.match(expression) || []).length;

const evaluate = (password, confirmation, policy) => ({
    minlength: password.length >= Number(policy.minlength || 0),
    lowercase: countMatches(password, /[a-z]/g) >= Number(policy.minlower || 0),
    uppercase: countMatches(password, /[A-Z]/g) >= Number(policy.minupper || 0),
    digit: countMatches(password, /[0-9]/g) >= Number(policy.mindigits || 0),
    special: countMatches(password, /[^a-zA-Z0-9]/g) >= Number(policy.minspecial || 0),
    match: password.length > 0 && password === confirmation,
});

const initialiseValidation = (password, confirmation, policy) => {
    const form = password.closest('form');
    if (!form) {
        return;
    }
    const submit = form.querySelector('button[type="submit"], input[type="submit"]');
    const rules = [...form.querySelectorAll('[data-password-rule]')];

    const refresh = () => {
        const state = evaluate(password.value, confirmation.value, policy);
        rules.forEach((element) => {
            const valid = Boolean(state[element.dataset.passwordRule]);
            element.classList.toggle('is-valid', valid);
            element.classList.toggle('is-invalid', !valid && (password.value !== '' || confirmation.value !== ''));
            const icon = element.querySelector('.commerce-guest-activation__requirement-icon');
            if (icon) {
                icon.textContent = valid ? '✓' : '•';
            }
        });
        const valid = Object.values(state).every(Boolean);
        if (submit) {
            submit.disabled = !valid;
            submit.setAttribute('aria-disabled', valid ? 'false' : 'true');
        }
        confirmation.setCustomValidity(state.match ? '' : 'password-mismatch');
    };

    password.addEventListener('input', refresh);
    confirmation.addEventListener('input', refresh);
    password.addEventListener('blur', refresh);
    confirmation.addEventListener('blur', refresh);
    refresh();
};

export const init = (
    showLabel = 'Afficher le mot de passe',
    hideLabel = 'Masquer le mot de passe',
    policy = {}
) => {
    const run = () => {
        const password = document.getElementById('id_password');
        const confirmation = document.getElementById('id_passwordconfirm');
        if (!(password instanceof HTMLInputElement) || !(confirmation instanceof HTMLInputElement)) {
            return;
        }
        const settings = {showLabel, hideLabel};
        enhanceInput(password, settings);
        enhanceInput(confirmation, settings);
        initialiseValidation(password, confirmation, policy);
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, {once: true});
    } else {
        run();
    }
};
