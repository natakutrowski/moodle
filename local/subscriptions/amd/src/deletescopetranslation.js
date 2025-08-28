/* eslint-disable no-undef */
/* global M */
define(['jquery', 'core/str', 'core/notification'], function($, str, notification) {
    return {
        init: function() {
            $(document).on('click', '.deletetranslation', function(e) {
                e.preventDefault();

                const $btn = $(this);
                // 1) Récup ID: data-id -> hidden input 'id'
                let id = $btn.attr('data-id') || $btn.data('id');
                if (!id) {
                    id = $('form').find('input[name="id"]').val(); // id de la traduction (hidden)
                }
                if (!id || String(id) === '0') {
                    // Rien à supprimer – on log et on sort proprement
                    console.warn('[subs] deletetranslation: no id found');
                    return;
                }

                const name = $btn.attr('data-name') || $btn.data('name') || '';
                const planid = $('form').find('input[name="planid"]').val() || '';

                // 2) Construire l’URL de delete (nettoyer les params 'edit', 'add', 'id')
                const url = new URL(M.cfg.wwwroot + '/local/subscriptions/scopes_translations.php');
                if (planid) url.searchParams.set('planid', planid);
                url.searchParams.set('del', id);
                url.searchParams.set('sesskey', M.cfg.sesskey);

                // 3) Confirmer puis rediriger
                str.get_strings([
                    {key: 'confirmdeletetranslation', component: 'local_subscriptions'},
                    {key: 'yes', component: 'moodle'},
                    {key: 'no', component: 'moodle'}
                ]).then(function([message, yes, no]) {
                    notification.confirm(name, message, yes, no, function() {
                        window.location.href = url.toString();
                    });
                });
            });
        }
    };
});
