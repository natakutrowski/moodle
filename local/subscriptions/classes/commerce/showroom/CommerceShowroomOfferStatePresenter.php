<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityPresenter;
use local_subscriptions\commerce\storefront\cart\CommerceStorefrontPromotionJoinCartContextResolver;

/** Applies generic runtime Commerce state to Showroom offers. */
final class CommerceShowroomOfferStatePresenter {
    /**
     * @param array<int,array<string,mixed>> $offers
     * @return array<int,array<string,mixed>>
     */
    public function present(array $offers): array {
        $capacitypresenter = CommercePedagogicalCapacityPresenter::create();
        $pedagogicalpresenter = CommerceShowroomPedagogicalPresenter::create();
        $now = time();

        foreach ($offers as &$offer) {
            $offersku = (string)($offer['sku'] ?? '');
            $excludedcartuuid = CommerceDirectPurchaseSession::cart_uuid_for_sku(
                $offersku
            );

            // Existing owners can hold a seat through the promotion_join
            // journey. Exclude their own hold exactly as Storefront does so a
            // return to the Showroom does not make the offer look sold out.
            if (
                !empty($offer['owned'])
                && isloggedin()
                && !isguestuser()
            ) {
                global $USER;
                $promotionjoincartuuid =
                    CommerceStorefrontPromotionJoinCartContextResolver::create()
                        ->excluded_cart_uuid(
                            (int)$USER->id,
                            (string)($offer['currency'] ?? ''),
                            $offersku
                        );
                if ($promotionjoincartuuid !== null) {
                    $excludedcartuuid = $promotionjoincartuuid;
                }
            }

            $capacity = $capacitypresenter->for_product(
                $offersku,
                $now,
                $excludedcartuuid
            );
            $pedagogy = $pedagogicalpresenter->for_product(
                $offersku,
                $now
            );
            $offer = array_merge($offer, $capacity, $pedagogy);

            if (
                !empty($offer['haspedagogicalcapacity'])
                && empty($offer['pedagogicalbuyavailable'])
            ) {
                $offer['canbuy'] = false;
            }

            $offer['cannotbuy'] = empty($offer['canbuy']);
            $offer['soldoutlabel'] = get_string(
                'commerce_capacity_sold_out_cta',
                'local_subscriptions'
            );
            $offer['ownedlabel'] = get_string(
                'commerce_storefront_owned',
                'local_subscriptions'
            );
            $offer['ownedcompactlabel'] = get_string(
                'commerce_showroom_owned_compact',
                'local_subscriptions'
            );
        }
        unset($offer);

        return $offers;
    }
}
