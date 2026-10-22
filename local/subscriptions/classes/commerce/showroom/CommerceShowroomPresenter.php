<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\url\UrlFactory;

/** Composes generic Showroom Commerce state with a presentation profile. */
final class CommerceShowroomPresenter {
    /** @param array<int,array<string,mixed>> $offers @return array<string,mixed> */
    public function present(
        CommerceShowroomDefinition $definition,
        array $offers,
        string $currency
    ): array {
        $offers = (new CommerceShowroomOfferStatePresenter())->present($offers);
        $profile = CommerceShowroomPresentationProfileRegistry::for_definition($definition);
        $offers = $profile->decorate_offers($offers);

        $data = $profile->fallback_data($offers);
        $pedagogicalcontext = CommerceShowroomPedagogicalPresenter::page_context($offers);
        $supportemail = (string)(
            get_config('local_subscriptions', 'support_email') ?: 'support@campusfr.fr'
        );

        return array_replace($data, $pedagogicalcontext, [
            'showroomkey' => $definition->get_key(),
            'title' => get_string($definition->get_title_key(), 'local_subscriptions'),
            'description' => get_string($definition->get_description_key(), 'local_subscriptions'),
            'videomodalclose' => get_string('commerce_showroom_video_close', 'local_subscriptions'),
            'offers' => $offers,
            'hasoffers' => $offers !== [],
            'supporturl' => UrlFactory::support([
                'source' => 'showroom',
                'showroom' => $definition->get_key(),
            ])->out(false),
            'supportemail' => $supportemail,
            'supportemailurl' => 'mailto:' . rawurlencode($supportemail),
            'supportimageurl' => (new \moodle_url(
                '/local/subscriptions/pix/support/gustave_support.png'
            ))->out(false),
            'currency' => $currency,
            'currencylabel' => get_string('currency_selector_label', 'local_subscriptions'),
            'showroomurl' => CommerceShowroomUrl::make(
                $definition,
                ['currency' => $currency]
            )->out(false),
            'shopurl' => UrlFactory::digital_catalog(['currency' => $currency])->out(false),
            'shoplabel' => get_string('commerce_showroom_back_to_shop', 'local_subscriptions'),
            'carturl' => UrlFactory::cart(['currency' => $currency])->out(false),
            'cartlabel' => get_string('commerce_cart_title', 'local_subscriptions'),
        ]);
    }
}
