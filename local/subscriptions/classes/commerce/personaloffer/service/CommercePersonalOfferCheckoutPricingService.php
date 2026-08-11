<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;

/** Resolves server-authoritative Personal Offer prices for cart/checkout lines. */
final class CommercePersonalOfferCheckoutPricingService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly MoodleCommercePersonalOfferRepository $offers
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self($db, new MoodleCommercePersonalOfferRepository($db));
    }

    public function resolve_unit_minor(
        string $offeruuid,
        string $productsku,
        string $currency,
        int $catalogunitminor,
        ?int $at = null
    ): int {
        $offer = $this->offers->get_by_uuid($offeruuid);
        $at ??= time();
        if ($offer === null || !$offer->is_available_at($at)) {
            throw new \moodle_exception('commerce_personal_offer_not_redeemable', 'local_subscriptions');
        }

        $product = $this->db->get_record('local_subs_commerce_product', ['id' => $offer->get_target_product_id()], 'id,sku', MUST_EXIST);
        if (strcasecmp((string)$product->sku, $productsku) !== 0) {
            throw new \moodle_exception('commerce_personal_offer_target_mismatch', 'local_subscriptions');
        }

        $terms = $offer->get_terms();
        $currency = strtoupper(trim($currency));
        return match ($terms->get_pricing_strategy()) {
            CommercePersonalOfferTerms::STRATEGY_FIXED_PRICE => $this->require_currency_amount($terms, $currency),
            CommercePersonalOfferTerms::STRATEGY_FIXED_DISCOUNT => max(0, $catalogunitminor - $this->require_currency_amount($terms, $currency)),
            CommercePersonalOfferTerms::STRATEGY_PERCENTAGE_DISCOUNT => max(0, $catalogunitminor - intdiv($catalogunitminor * (int)$terms->get_percentage_basispoints(), 10000)),
            default => throw new \coding_exception('Unsupported Personal Offer pricing strategy.'),
        };
    }

    private function require_currency_amount(CommercePersonalOfferTerms $terms, string $currency): int {
        $amount = $terms->get_amount_for_currency($currency);
        if ($amount === null) {
            throw new \moodle_exception('commerce_personal_offer_currency_unavailable', 'local_subscriptions');
        }
        return $amount;
    }
}
