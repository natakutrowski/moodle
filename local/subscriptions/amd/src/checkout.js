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
            // Mot de passe invité : longueur mini 8
            if (el.id === 'checkoutPass' && v.length < 8) return false;
        }
        return true;
    }


    function getChosenRadio(form) {
        return qs('input[type="radio"][name="operation"]:checked', form);
    }


    function getTerms(form) {
        // compat: accepte #agree_terms (nouveau) et #consent (ancien)
        return qs('#agree_terms', form) || qs('#consent', form);
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

    function updateSummary(form) {
        const radios  = qsa('input[type="radio"][name="operation"]', form);
        const summary = qs('#ls_price_summary', form) || qs('#ls_price_summary');
        if (!summary) return;

        // AUCUN RADIO => cas 1 seule option : on laisse le résumé rendu par PHP
        if (!radios.length) {
            return;
        }

        const chosen  = qs('input[type="radio"][name="operation"]:checked', form);

        if (!chosen) {
            str.get_string('summary_price_wait', 'local_subscriptions')
                .done(s => { summary.textContent = s; })
                .fail(() => { summary.textContent = '...'; });
            return;
        }

        const ds = chosen.dataset || {};
        const isUp    = (ds.isupgrade === '1') || (chosen.value || '').indexOf('upgrade_') === 0;
        const discPct = parseFloat(ds.discPct || '0');

        const baseDisp   = ds.baseDisplay   || '';
        const finalDisp  = ds.finalDisplay  || '';
        const amountDisp = ds.amountDisplay || '';
        const baseNum    = parseFloat(ds.base   || '0');
        const amountNum  = parseFloat(ds.amount || '0');

        // Cas 1 : remise d’essai active sur "nouvel abonnement" → afficher catalogue barré + final en vert
        if (finalDisp && !isNaN(discPct) && discPct > 0 && !isUp) {
            summary.innerHTML =
                '<span class="text-muted text-decoration-line-through me-2">' + baseDisp  + '</span>' +
                '<span class="fw-semibold text-success">'                    + finalDisp + '</span>';
            return;
        }

        // Cas 2 : upgrade moins cher → barré + amount (Advisor) en vert
        if (isUp && amountNum < baseNum && baseDisp && amountDisp) {
            summary.innerHTML =
                '<span class="text-muted text-decoration-line-through me-2">' + baseDisp    + '</span>' +
                '<span class="fw-semibold text-success">'                    + amountDisp  + '</span>';
            return;
        }

        // Cas 3 : affichage simple (pas de remise ou pas d’upgrade)
        summary.innerHTML = '<span class="fw-semibold">' + (amountDisp || baseDisp) + '</span>';
    }

    function validate(form) {
        const btn = qs('button[type="submit"]', form);
        if (!btn) return false;

        const mode  = btn.getAttribute('data-mode') || '';
        const terms = getTerms(form);

        const termsOk  = terms  ? !!terms.checked  : true;

        const radios    = qsa('input[type="radio"][name="operation"]', form);
        const hasRadios = radios.length > 0;
        const chosen    = hasRadios ? !!getChosenRadio(form) : true;


        if (mode === 'user') {
            return chosen && termsOk;
        } else {
            return chosen && termsOk && fieldsOk(form);
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
        const radios = qsa('input[name="operation"]', form);
        const requiredInputs = qsa('.ls-fields input[required]', form);

        // init
        updateSummary(form);
        applyButtonState(form);
        syncExtraJson(form);

        // écoutes
        // APRÈS – on n’écoute QUE 'change' pour éviter le double-déclenchement avant le .checked
        radios.forEach(r => {
            r.addEventListener('change', function() {
                syncExtraJson(form);
                updateSummary(form);
                applyButtonState(form);
            });
        });

        if (terms) {
            ['change','click'].forEach(e => terms.addEventListener(e, function() {
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
