// This file is part of Moodle - http://moodle.org/
//
// Context-aware provider transition experience.

import Config from 'core/config';

const SELECTOR_FORM = '[data-provider-experience]';
const SELECTOR_DIALOG = '[data-provider-experience-dialog]';
const SELECTOR_CURRENCY_DIALOG = '[data-provider-currency-dialog]';
const SELECTOR_PROVIDER = 'input[name="provider"]:checked';
const CONTEXT_STANDARD = 'standard';
const CONTEXT_EXPRESS_CANDIDATE = 'express-candidate';

const bypassForms = new WeakSet();
let activeForm = null;
let activeProviderDialog = null;

const resolveProvider = form => {
    const selected = form.querySelector(SELECTOR_PROVIDER);
    if (selected) {
        return String(selected.value || '').toLowerCase();
    }

    const explicit = String(form.dataset.provider || '').toLowerCase();
    if (explicit) {
        return explicit;
    }

    const currencyInput = form.querySelector('input[name="currency"]');
    const currency = String(currencyInput?.value || form.dataset.currency || '').toUpperCase();
    return currency === 'RUB' ? 'alfa' : 'stripe';
};

const resolveCurrency = form => {
    const input = form.querySelector('input[name="currency"]');
    return String(input?.value || form.dataset.currency || '').toUpperCase();
};

const resolvePrice = form => {
    if (String(form.dataset.price || '').trim() !== '') {
        return String(form.dataset.price).trim();
    }

    const scope = form.closest(
        '[data-showroom-offer], .commerce-product-card, .commerce-product-commerce-panel, .commerce-checkout__payment'
    );
    const price = scope?.querySelector(
        '[data-showroom-price], .commerce-storefront-price__values strong, .commerce-checkout__grand-total dd'
    );
    return String(price?.textContent || '').trim();
};

const stringFor = (dialog, provider, key, context) => {
    const contextKey = `${provider}${context.charAt(0).toUpperCase()}${context.slice(1)}`
        + `${key.charAt(0).toUpperCase()}${key.slice(1)}`;
    const providerKey = `${provider}${key.charAt(0).toUpperCase()}${key.slice(1)}`;
    return dialog.dataset[contextKey] || dialog.dataset[providerKey] || dialog.dataset[key] || '';
};

const populate = (dialog, form, provider, context) => {
    const title = dialog.querySelector('[data-provider-experience-title]');
    const message = dialog.querySelector('[data-provider-experience-message]');
    const advice = dialog.querySelector('[data-provider-experience-advice]');
    const continueButton = dialog.querySelector('[data-provider-experience-continue]');
    const secondaryButton = dialog.querySelector('[data-provider-experience-secondary]');
    const product = dialog.querySelector('[data-provider-experience-product]');
    const price = dialog.querySelector('[data-provider-experience-price]');
    const icon = dialog.querySelector('[data-provider-experience-icon]');

    title.textContent = stringFor(dialog, provider, 'title', context);
    message.textContent = stringFor(dialog, provider, 'message', context);
    continueButton.textContent = stringFor(dialog, provider, 'continue', context);
    secondaryButton.textContent = stringFor(dialog, provider, 'secondary', context);
    advice.textContent = stringFor(dialog, provider, 'advice', context);
    advice.hidden = advice.textContent.trim() === '';

    const productLabel = String(form.dataset.product || '').trim();
    const priceLabel = resolvePrice(form);
    product.textContent = productLabel;
    product.hidden = productLabel === '';
    price.textContent = priceLabel;
    price.hidden = priceLabel === '';

    icon.className = provider === 'alfa'
        ? 'fa-solid fa-triangle-exclamation'
        : 'fa-solid fa-shield-halved';
    dialog.dataset.activeProvider = provider;
    dialog.dataset.activeContext = context;
};

const open = (form, provider, context) => {
    const dialog = document.querySelector(SELECTOR_DIALOG);
    if (!dialog) {
        bypassForms.add(form);
        form.requestSubmit();
        return;
    }

    activeForm = form;
    activeProviderDialog = dialog;
    populate(dialog, form, provider, context);

    if (typeof dialog.showModal === 'function') {
        dialog.showModal();
    } else {
        dialog.setAttribute('open', 'open');
    }
};

const close = dialog => {
    if (typeof dialog.close === 'function') {
        dialog.close();
    } else {
        dialog.removeAttribute('open');
    }
};

const resetActive = () => {
    activeForm = null;
    activeProviderDialog = null;
};

const continueToProvider = dialog => {
    if (!activeForm) {
        close(dialog);
        resetActive();
        return;
    }

    const form = activeForm;
    appendHidden(form, 'providerconfirmed', '1');
    bypassForms.add(form);
    close(dialog);
    resetActive();
    form.requestSubmit();
};

const isExpressEligible = async form => {
    const currency = resolveCurrency(form);
    const sesskey = form.querySelector('input[name="sesskey"]')?.value || Config.sesskey || '';
    if (!currency || !sesskey) {
        return false;
    }

    const body = new URLSearchParams({
        currency,
        sesskey,
        sku: hiddenValue(form, 'sku'),
        priceid: hiddenValue(form, 'priceid'),
        quantity: hiddenValue(form, 'quantity') || '1',
        operation: hiddenValue(form, 'operation'),
    });
    try {
        const response = await fetch(`${Config.wwwroot}/local/subscriptions/ajax/checkout_express_eligibility.php`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString(),
        });
        if (!response.ok) {
            return false;
        }
        const payload = await response.json();
        return payload.success === true && payload.eligible === true;
    } catch (error) {
        return false;
    }
};

const hiddenValue = (form, name) => form.querySelector(`[name="${name}"]`)?.value || '';

const appendHidden = (form, name, value) => {
    if (String(value) === '') {
        return;
    }
    let input = form.querySelector(`[data-provider-currency-copy="${name}"]`);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.dataset.providerCurrencyCopy = name;
        form.appendChild(input);
    }
    input.value = String(value);
};

const loadCurrencies = async currentCurrency => {
    const response = await fetch(`${Config.wwwroot}/local/subscriptions/ajax/cart_currencies.php`, {
        credentials: 'same-origin',
        headers: {'Accept': 'application/json'},
    });
    if (!response.ok) {
        throw new Error('currency-list');
    }
    const payload = await response.json();
    if (payload.success !== true || !Array.isArray(payload.currencies)) {
        throw new Error('currency-list');
    }
    return payload.currencies.filter(item => String(item.code || '').toUpperCase() !== currentCurrency);
};

const renderCurrencyChoices = (dialog, currencies) => {
    const choices = dialog.querySelector('[data-provider-currency-choices]');
    const submit = dialog.querySelector('[data-provider-currency-submit]');
    choices.textContent = '';
    submit.disabled = true;

    currencies.forEach((currency, index) => {
        const label = document.createElement('label');
        label.className = 'commerce-provider-currency__choice';

        const input = document.createElement('input');
        input.type = 'radio';
        input.name = 'targetcurrency';
        input.value = String(currency.code || '').toUpperCase();
        input.required = true;
        if (index === 0) {
            input.checked = true;
            submit.disabled = false;
        }

        const content = document.createElement('span');
        content.className = 'commerce-provider-currency__choice-content';
        content.innerHTML = `<strong>${String(currency.code || '')}</strong><span>${String(currency.symbol || '')}</span>`;
        label.append(input, content);
        choices.appendChild(label);
    });

    choices.addEventListener('change', () => {
        submit.disabled = !choices.querySelector('input[name="targetcurrency"]:checked');
    }, {once: true});
};

const openCurrencyDialog = async providerDialog => {
    if (!activeForm) {
        return;
    }

    const currencyDialog = document.querySelector(SELECTOR_CURRENCY_DIALOG);
    if (!currencyDialog) {
        return;
    }

    const currentCurrency = resolveCurrency(activeForm);
    const status = currencyDialog.querySelector('[data-provider-currency-status]');
    const form = currencyDialog.querySelector('[data-provider-currency-form]');
    const title = currencyDialog.querySelector('[data-provider-currency-title]');
    const message = currencyDialog.querySelector('[data-provider-currency-message]');
    const submit = currencyDialog.querySelector('[data-provider-currency-submit]');

    title.textContent = currencyDialog.dataset.title || '';
    message.textContent = currencyDialog.dataset.message || '';
    submit.textContent = currencyDialog.dataset.submit || '';
    status.textContent = '';
    form.action = `${Config.wwwroot}/local/subscriptions/cart_action.php`;
    form.querySelector('[data-provider-currency-source]').value = currentCurrency;
    form.querySelector('[data-provider-currency-sesskey]').value = Config.sesskey || hiddenValue(activeForm, 'sesskey');

    ['sku', 'priceid', 'quantity', 'source', 'showroom', 'showroomoffer', 'returnurl'].forEach(name => {
        appendHidden(form, name, hiddenValue(activeForm, name));
    });

    close(providerDialog);
    try {
        const currencies = await loadCurrencies(currentCurrency);
        if (!currencies.length) {
            status.textContent = currencyDialog.dataset.empty || '';
            submit.disabled = true;
        } else {
            renderCurrencyChoices(currencyDialog, currencies);
        }
    } catch (error) {
        status.textContent = currencyDialog.dataset.error || '';
        submit.disabled = true;
    }

    if (typeof currencyDialog.showModal === 'function') {
        currencyDialog.showModal();
    } else {
        currencyDialog.setAttribute('open', 'open');
    }
};

export const init = () => {
    const forms = document.querySelectorAll(SELECTOR_FORM);
    const dialog = document.querySelector(SELECTOR_DIALOG);
    const currencyDialog = document.querySelector(SELECTOR_CURRENCY_DIALOG);

    if (!forms.length || !dialog || dialog.dataset.providerExperienceBound === '1') {
        return;
    }

    dialog.dataset.providerExperienceBound = '1';

    forms.forEach(form => {
        form.addEventListener('submit', async event => {
            if (bypassForms.has(form)) {
                bypassForms.delete(form);
                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            const context = String(form.dataset.providerContext || CONTEXT_EXPRESS_CANDIDATE);
            const provider = resolveProvider(form);

            if (context === CONTEXT_STANDARD) {
                if (provider !== 'alfa') {
                    return;
                }
                event.preventDefault();
                open(form, provider, context);
                return;
            }

            event.preventDefault();
            const eligible = await isExpressEligible(form);
            if (!eligible) {
                bypassForms.add(form);
                form.requestSubmit();
                return;
            }

            open(form, provider, context);
        });
    });

    dialog.addEventListener('click', event => {
        if (event.target === dialog) {
            close(dialog);
            resetActive();
        }
    });

    dialog.querySelector('[data-provider-experience-close]')?.addEventListener('click', () => {
        close(dialog);
        resetActive();
    });
    dialog.querySelector('[data-provider-experience-continue]')?.addEventListener(
        'click',
        () => continueToProvider(dialog)
    );
    dialog.querySelector('[data-provider-experience-secondary]')?.addEventListener('click', () => {
        if (dialog.dataset.activeProvider === 'alfa') {
            openCurrencyDialog(dialog);
        } else {
            close(dialog);
            resetActive();
        }
    });

    dialog.addEventListener('cancel', event => {
        event.preventDefault();
        close(dialog);
        resetActive();
    });

    if (currencyDialog) {
        currencyDialog.querySelector('[data-provider-currency-close]')?.addEventListener('click', () => {
            close(currencyDialog);
            resetActive();
        });
        currencyDialog.querySelector('[data-provider-currency-back]')?.addEventListener('click', () => {
            close(currencyDialog);
            if (activeProviderDialog) {
                activeProviderDialog.showModal();
            }
        });
        currencyDialog.addEventListener('cancel', event => {
            event.preventDefault();
            close(currencyDialog);
            resetActive();
        });
        currencyDialog.addEventListener('click', event => {
            if (event.target === currencyDialog) {
                close(currencyDialog);
                resetActive();
            }
        });
    }
};

export default {
    init,
};