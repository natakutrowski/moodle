/* eslint-disable no-undef */
define(['jquery', 'core/notification', 'core/str'], function($, Notification, Str) {
    return {
        init: function() {
            $('body').on('click', '.deleteplan', function(e) {
                e.preventDefault();

                const $link = $(this);
                const url = $link.data('deleteurl');
                const rawname = $link.data('name');

                // Si le nom est vide, charger "ce plan"
                const namePromise = rawname
                    ? Promise.resolve(rawname)
                    : Str.get_string('thisplan', 'local_subscriptions');

                Promise.all([
                    Str.get_strings([
                        {key: 'confirmdeletetitle', component: 'local_subscriptions'},
                        {key: 'delete', component: 'local_subscriptions'},
                        {key: 'cancel', component: 'local_subscriptions'}
                    ]),
                    namePromise.then(name =>
                        Str.get_string('confirmdeleteplanmessage', 'local_subscriptions', name)
                    )
                ]).then(function([[title, deleteLabel, cancelLabel], message]) {
                    Notification.confirm(
                        title,
                        message,
                        deleteLabel,
                        cancelLabel,
                        () => { window.location.href = url; }
                    );
                }).catch(Notification.exception);
            });
        }
    };
});
