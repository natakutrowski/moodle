/* eslint-disable no-undef */
define([], function() {
    const SELECTOR = '[data-storefront-owned-toggle]';

    const init = function() {
        const toggle = document.querySelector(SELECTOR);
        if (!toggle) {
            return;
        }

        toggle.addEventListener('change', function() {
            const form = toggle.closest('form');
            if (!form) {
                return;
            }

            form.submit();
        });
    };

    return {
        init: init,
    };
});
