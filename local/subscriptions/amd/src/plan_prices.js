/* eslint-disable no-undef */
define(['jquery'], function($) {
    return {
        init: function() {

            $('.change-currency-link').on('click', function(e) {
                e.preventDefault();
                const planid = $(this).data('planid');

                // Masquer le lien et le prix
                $(this).hide();
                $(`.selected-price[data-planid="${planid}"]`).hide();

                // Afficher le sélecteur
                $(`#currency-selector-${planid}`).show();
            });

            $('.currency-selector').on('change', function() {
                const planid = $(this).data('planid');
                const selectedOption = $(this).find('option:selected');
                const newPrice = selectedOption.data('price');
                const newCurrency = selectedOption.val();

                const priceSpan = $(`.selected-price[data-planid="${planid}"]`);
                priceSpan.html(`<strong>${newPrice} ${newCurrency}</strong>`).show();

                // Masquer le sélecteur
                $(this).hide();

                // Réafficher le lien "Changer la devise"
                $(`.change-currency-link[data-planid="${planid}"]`).show();
            });

        }
    };
});
