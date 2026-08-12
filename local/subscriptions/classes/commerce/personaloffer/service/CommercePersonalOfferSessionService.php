<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;

/** Stores a non-authoritative Personal Offer navigation context in the Moodle session. */
final class CommercePersonalOfferSessionService {
    private const CONTEXT_VERSION = 1;

    /**
     * @param array{destination:string,campaignid:?int,showroomid:?int,showroomkey:?string} $destination
     */
    public function initialise(
        string $token,
        CommercePersonalOffer $offer,
        string $sku,
        string $currency,
        array $destination
    ): void {
        global $SESSION;

        $currency = strtoupper(trim($currency));
        $SESSION->local_subscriptions_personal_offer_token = $token;
        $SESSION->local_subscriptions_personal_offer_uuid = $offer->get_offer_uuid();
        $SESSION->local_subscriptions_personal_offer_context = [
            'version' => self::CONTEXT_VERSION,
            'offeruuid' => $offer->get_offer_uuid(),
            'campaignkey' => (string)($offer->get_campaign_key() ?? ''),
            'campaignid' => $destination['campaignid'] ?? null,
            'targetproductid' => $offer->get_target_product_id(),
            'sku' => strtoupper(trim($sku)),
            'currency' => $currency,
            'destination' => (string)$destination['destination'],
            'showroomid' => $destination['showroomid'] ?? null,
            'showroomkey' => $destination['showroomkey'] ?? null,
            'initialisedat' => time(),
        ];
    }

    public function clear(): void {
        global $SESSION;
        unset(
            $SESSION->local_subscriptions_personal_offer_token,
            $SESSION->local_subscriptions_personal_offer_uuid,
            $SESSION->local_subscriptions_personal_offer_context
        );
    }
}
