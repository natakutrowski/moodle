// Moodle login form enhancement: explicit, opt-in 30-day persistent authentication.
(function() {
    'use strict';
    function init() {
        var form = document.querySelector('form#login') || document.querySelector('form.login-form');
        if (!form || !form.querySelector('input[name="password"]') ||
                form.querySelector('input[name="campusremember"]')) {
            return;
        }
        var lang = (document.documentElement.lang || 'en').toLowerCase();
        var text = lang.startsWith('ru') ? 'Оставаться в системе 30 дней на этом устройстве' :
            lang.startsWith('fr') ? 'Rester connecté pendant 30 jours sur cet appareil' :
            'Keep me signed in for 30 days on this device';
        var box = document.createElement('div');
        box.className = 'form-check mb-3 campusremember-optin';
        var input = document.createElement('input');
        input.type = 'checkbox';
        input.id = 'campusremember';
        input.name = 'campusremember';
        input.value = '1';
        input.checked = false;
        input.className = 'form-check-input';
        var label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = input.id;
        label.textContent = text;
        box.append(input, label);
        var submit = form.querySelector('.login-form-submit') || form.querySelector('button[type="submit"]');
        if (submit) {
            submit.parentNode.insertBefore(box, submit);
        } else {
            form.appendChild(box);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
