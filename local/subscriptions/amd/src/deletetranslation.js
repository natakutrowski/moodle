/* eslint-disable no-undef */
/* global M */
define(['jquery', 'core/str', 'core/notification'], function($, str, notification) {
    return {
        init: function() {
            $('body').on('click', '.deletetranslation', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const name = $(this).data('name') || '';
                const deleteurl = new URL(window.location.href);
                deleteurl.searchParams.set('del', id);
                deleteurl.searchParams.set('sesskey', M.cfg.sesskey);

                str.get_strings([
                    {key: 'confirmdeletetranslation', component: 'local_subscriptions'},
                    {key: 'yes', component: 'moodle'},
                    {key: 'no', component: 'moodle'}
                ]).then(function([message, yes, no]) {
                    notification.confirm(name, message, yes, no, function() {
                        window.location.href = deleteurl.toString();
                    });
                });
            });
        }
    };
});
