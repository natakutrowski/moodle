/* eslint-disable no-undef */
/* global M */
define(['jquery', 'core/str', 'core/notification'], function($, str, notification) {
    return {
        init: function() {
            $('body').on('click', '.deleteprice', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const currency = $(this).data('currency') || '';
                const deleteurl = new URL(window.location.href);
                deleteurl.searchParams.set('del', id);
                deleteurl.searchParams.set('sesskey', M.cfg.sesskey);

                str.get_strings([
                    {key: 'confirmdeleteprice', component: 'local_subscriptions'},
                    {key: 'yes', component: 'moodle'},
                    {key: 'no', component: 'moodle'}
                ]).then(function([message, yes, no]) {
                    notification.confirm(currency, message, yes, no, function() {
                        window.location.href = deleteurl.toString();
                    });
                });
            });
        }
    };
});
