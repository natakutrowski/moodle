/* eslint-disable no-undef */
define([], function() {
    function validEmail(s) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s || '');
    }
    function validate(form) {
        const mode = form.querySelector('button[type="submit"]').getAttribute('data-mode');
        const consent = form.querySelector('#consent');
        if (mode === 'user') {
            return consent && consent.checked;
        }
        const email = form.querySelector('input[name="email"]');
        const fn = form.querySelector('input[name="firstname"]');
        const ln = form.querySelector('input[name="lastname"]');
        const ok = validEmail(email.value) && (fn.value || '').trim().length >= 2 && (ln.value || '').trim().length >= 2;
        return ok && consent && consent.checked;
    }
    function wire(form) {
        const btn = form.querySelector('button[type="submit"]');
        const toggle = () => { btn.disabled = !validate(form); };
        // init
        toggle();
        form.querySelectorAll('input').forEach(el => {
            ['input','change','blur'].forEach(e => el.addEventListener(e, toggle));
        });
    }
    function init() {
        document.querySelectorAll('form.ls-checkout-form').forEach(wire);
    }
    return { init };
});
