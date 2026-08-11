<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

final class commerce_personal_offer_model_test extends advanced_testcase {
    public function test_fixed_price_terms_support_multiple_currencies(): void {
        $terms = CommercePersonalOfferTerms::fixed_price(['rub' => 299000, 'EUR' => 3000]);
        $this->assertSame(CommercePersonalOfferTerms::STRATEGY_FIXED_PRICE, $terms->get_pricing_strategy());
        $this->assertSame(3000, $terms->get_amount_for_currency('eur'));
        $this->assertSame(299000, $terms->get_amount_for_currency('RUB'));
    }

    public function test_percentage_terms_validate_basis_points(): void {
        $terms = CommercePersonalOfferTerms::percentage_discount(2000);
        $this->assertSame(2000, $terms->get_percentage_basispoints());

        $this->expectException(\coding_exception::class);
        CommercePersonalOfferTerms::percentage_discount(10001);
    }

    public function test_expiration_is_effective_state_without_mutating_persisted_status(): void {
        $offer = $this->make_offer(expiresat: 2000);
        $this->assertSame(CommercePersonalOffer::STATUS_ISSUED, $offer->get_status());
        $this->assertSame(CommercePersonalOffer::EFFECTIVE_EXPIRED, $offer->get_effective_status(2001));
        $this->assertFalse($offer->is_available_at(2001));
    }

    public function test_redeemed_offer_requires_redemption_evidence(): void {
        $this->expectException(\coding_exception::class);
        new CommercePersonalOffer(
            null,
            str_repeat('a', 32),
            'campaign',
            null,
            10,
            null,
            'buyer@example.com',
            CommercePersonalOffer::STATUS_REDEEMED,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])
        );
    }

    private function make_offer(?int $expiresat = null): CommercePersonalOffer {
        return new CommercePersonalOffer(
            null,
            str_repeat('a', 32),
            'trainer-launch',
            12,
            34,
            null,
            'buyer@example.com',
            CommercePersonalOffer::STATUS_ISSUED,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            null,
            $expiresat
        );
    }
}
