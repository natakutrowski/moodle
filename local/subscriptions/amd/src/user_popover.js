/* eslint-disable no-undef */
define(['jquery'], function($) {
    return {
        init: function() {
            $(function() {
                // Active tous les popovers Bootstrap (si le JS Bootstrap est chargé globalement)
                $('[data-bs-toggle="popover"]').popover({
                    html: true,
                    trigger: 'click',
                    sanitize: false
                });

                // Gère la fermeture avec la croix
                $(document).on('click', '.close-popover', function() {
                    const $popover = $(this).closest('.popover');
                    const popoverId = $popover.attr('id');
                    if (popoverId) {
                        const $trigger = $('[aria-describedby="' + popoverId + '"]');
                        if ($trigger.length) {
                            $trigger.popover('hide');
                        }
                    }
                });
            });
        }
    };
});
