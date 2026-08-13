// M8E.2 — premium transition from CampusFR checkout to payment provider.

const FORM_SELECTOR = 'form[data-provider-experience]';
const SPLASH_SELECTOR = '[data-payment-provider-transition]';

const selectedProvider = (form) => {
    const input = form.querySelector('input[name="provider"]:checked');
    return input ? String(input.value || '').toLowerCase() : '';
};

const show = (splash, provider) => {
    splash.dataset.provider = provider;
    splash.classList.add('is-visible');
    splash.removeAttribute('aria-hidden');

    const providerLabel = splash.querySelector('[data-transition-provider]');
    if (providerLabel) {
        providerLabel.textContent = provider === 'alfa'
            ? splash.dataset.alfaLabel
            : splash.dataset.defaultLabel;
    }
};

const hide = (splash) => {
    splash.classList.remove('is-visible');
    splash.setAttribute('aria-hidden', 'true');
};

export const init = () => {
    const splash = document.querySelector(SPLASH_SELECTOR);
    if (!splash) {
        return;
    }

    document.querySelectorAll(FORM_SELECTOR).forEach(form => {
        form.addEventListener('submit', event => {
            // Respect native/browser and existing Campus validation first.
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                hide(splash);
                return;
            }

            const provider = selectedProvider(form);
            if (!provider) {
                hide(splash);
                return;
            }

            show(splash, provider);

            // If another validation listener cancels submission synchronously,
            // release the overlay again on the next frame.
            window.requestAnimationFrame(() => {
                if (event.defaultPrevented) {
                    hide(splash);
                }
            });
        });
    });

    // BFCache / browser-back must never leave the splash covering checkout.
    window.addEventListener('pageshow', () => hide(splash));
};
