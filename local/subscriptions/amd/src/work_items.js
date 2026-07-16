/* eslint-env amd */
define([], function() {
    'use strict';

    const SELECTOR = '.crm-work-action-form';

    const announce = function(message) {
        let region = document.querySelector('[data-work-live-region]');
        if (!region) {
            region = document.createElement('div');
            region.className = 'sr-only';
            region.setAttribute('aria-live', 'polite');
            region.setAttribute('data-work-live-region', '1');
            document.body.appendChild(region);
        }
        region.textContent = '';
        window.setTimeout(function() {
            region.textContent = message;
        }, 10);
    };

    const bind = function(form) {
        if (form.dataset.workBound === '1') {
            return;
        }
        form.dataset.workBound = '1';
        form.addEventListener('submit', function() {
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button, input[type="submit"]').forEach(function(control) {
                control.disabled = true;
            });
            announce('Traitement en cours');
        });
    };

    const init = function() {
        document.querySelectorAll(SELECTOR).forEach(bind);
        window.addEventListener('pageshow', function() {
            document.querySelectorAll(SELECTOR).forEach(function(form) {
                form.removeAttribute('aria-busy');
                form.querySelectorAll('button, input[type="submit"]').forEach(function(control) {
                    control.disabled = false;
                });
            });
        });
    };

    return {init: init};
});