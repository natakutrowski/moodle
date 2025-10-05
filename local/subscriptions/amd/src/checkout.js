/* eslint-disable no-undef */
define(['core/str'], function(str) {
    function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
    function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function validEmail(s) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((s || '').trim());
    }

    function fieldsOk(form) {
        const req = qsa('.ls-fields input[required]', form);
        if (!req.length) return true; // connecté
        for (const el of req) {
            const v = (el.value || '').trim();
            if (!v) return false;
            if (el.type === 'email' && !validEmail(v)) return false;
        }
        return true;
    }

    function getChosenRadio(form) {
        return qs('input[name="operation"]:checked', form);
    }

    function getTerms(form) {
        // compat: accepte #agree_terms (nouveau) et #consent (ancien)
        return qs('#agree_terms', form) || qs('#consent', form);
    }

    function getPolicy(form) {
        return qs('#accept_privacy', form);
    }

    function getExtraHidden(form) {
        return qs('#ls_extra_json', form) || qs('#ls_extra_json'); // hidden ajouté côté PHP
    }

    function syncExtraJson(form) {
        const hidden = getExtraHidden(form);
        const chosen = getChosenRadio(form);
        if (!hidden) return;
        hidden.value = chosen ? (chosen.getAttribute('data-extra') || '') : '';
    }

    function numberFmt(n) {
        try {
            return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(+n);
        } catch (e) {
            return (+n).toFixed(2);
        }
    }

    function updateSummary(form) {
        const chosen = getChosenRadio(form);
        const summary = qs('#ls_price_summary', form) || qs('#ls_price_summary');
        if (!summary) return;

        if (!chosen) {
            str.get_string('summary_price_wait', 'local_subscriptions')
                .done(function(s) { summary.textContent = s; })
                .fail(function()  { summary.textContent = '...'; });
            return;
        }
        const amount   = parseFloat(chosen.getAttribute('data-amount') || '0');
        const currency = (chosen.getAttribute('data-currency') || '').toUpperCase();
        // upgrade si data-isupgrade=1 OU si la valeur commence par "upgrade_"
        const isUp = (chosen.getAttribute('data-isupgrade') === '1') || (chosen.value || '').indexOf('upgrade_') === 0;
        const base     = parseFloat(chosen.getAttribute('data-base') || '0');

        if (isUp && amount < base) {
            summary.innerHTML =
                '<span class="text-muted text-decoration-line-through me-2">' + numberFmt(base) + ' ' + currency + '</span>' +
                '<span class="fw-semibold text-success">' + numberFmt(amount) + ' ' + currency + '</span>';
        } else {
            summary.innerHTML = '<span class="fw-semibold">' + numberFmt(amount) + ' ' + currency + '</span>';
        }
    }

    function validate(form) {
        const btn = qs('button[type="submit"]', form);
        const mode = btn ? (btn.getAttribute('data-mode') || '') : '';
        const terms = getTerms(form);
        const policy = getPolicy(form);

        const termsOk = terms ? !!terms.checked : true; // si pas de case, on n’en tient pas compte
        const policyOk = policy ? !!policy.checked : true;

        const chosen = !!getChosenRadio(form);

        if (mode === 'user') {
            return chosen && termsOk && policyOk;
        } else {
            // invité : radios + terms + champs requis valides
            return chosen && termsOk && policyOk && fieldsOk(form);
        }
    }

    function applyButtonState(form) {
        const btn = qs('button[type="submit"]', form);
        if (!btn) return;
        const ok = validate(form);
        if (ok) btn.removeAttribute('disabled');
        else    btn.setAttribute('disabled', 'disabled');
    }

    function wire(form) {
        const terms = getTerms(form);
        const policy = getPolicy(form);
        const radios = qsa('input[name="operation"]', form);
        const requiredInputs = qsa('.ls-fields input[required]', form);

        // init
        updateSummary(form);
        applyButtonState(form);
        syncExtraJson(form);

        // écoutes
        radios.forEach(r => {
            ['change','click'].forEach(e => r.addEventListener(e, function() {
                syncExtraJson(form);      
                updateSummary(form);
                applyButtonState(form);
            }));
        });

        if (terms) {
            ['change','click'].forEach(e => terms.addEventListener(e, function() {
                applyButtonState(form);
            }));
        }
        
        if (policy) {
            ['change','click'].forEach(e => policy.addEventListener(e, function() {
                applyButtonState(form);
            }));
        }

        requiredInputs.forEach(el => {
            ['input','change','blur'].forEach(e => el.addEventListener(e, function() {
                applyButtonState(form);
            }));
        });

        // Sécurité : si d’autres scripts touchent au DOM après coup
        window.addEventListener('load', function() {
            setTimeout(function(){ updateSummary(form); applyButtonState(form); }, 0);
            setTimeout(function(){ updateSummary(form); applyButtonState(form); }, 50);
        });
    }

    function init() {
        qsa('form.ls-checkout-form').forEach(wire);
    }

    return { init };
});
