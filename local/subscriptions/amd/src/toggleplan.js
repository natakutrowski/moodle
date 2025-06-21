/* eslint-disable no-undef */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, notification) {
    return {
        init: function() {
            $('.toggleplan').on('click', function(e) {
                e.preventDefault();

                const $link = $(this);
                const planid = $link.data('id');
                const $icon = $link.find('i');

                const $row = $link.closest('tr'); // 🔍 Trouve la ligne <tr> contenant le lien

                // Sauvegarde les classes de base
                const originalClasses = $icon.attr('class');

                // Affiche le spinner et désactive temporairement
                $link.addClass('disabled');
                $icon.attr('class', 'fa fa-spinner fa-spin');

                Ajax.call([{
                    methodname: 'local_subscriptions_toggle_plan',
                    args: { id: planid },
                    done: function(result) {
                        const newClass = result.is_active
                            ? 'fa fa-eye'
                            : 'fa fa-eye-slash';

                        $icon.attr('class', newClass);
                        $link.attr('title', result.label);
                        $icon.attr('title', result.label);
                        $icon.attr('data-state', result.is_active ? 'active' : 'inactive');

                        // ✅ Ajoute ou retire la classe de style de la ligne
                        if (result.is_active) {
                            $row.removeClass('plan-inactive');
                        } else {
                            $row.addClass('plan-inactive');
                        }

                        $link.removeClass('disabled');
                    },
                    fail: function(err) {
                        notification.exception(err);

                        // Remet les icônes d’origine si erreur
                        $icon.attr('class', originalClasses);
                        $link.removeClass('disabled');
                    }
                }]);
            });
        }
    };
});
