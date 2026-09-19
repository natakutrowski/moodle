<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferPricingPresentationBuilder;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\currency\CurrencyFormatter;

/**
 * Commerce 7.96B2 currency-aware presentation integration tests.
 *
 * @covers \local_subscriptions\currency\CurrencyFormatter
 * @covers \local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation
 * @covers \local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferPricingPresentationBuilder
 */
final class commerce_796b2_currency_presentation_test extends advanced_testcase {

    public function test_iso_code_formatter_respects_currency_minor_units(): void {
        force_current_language('en');

        $this->assertSame("149\u{00A0}JPY", CurrencyFormatter::format_minor_code(149, 'JPY'));
        $this->assertSame("149.00\u{00A0}EUR", CurrencyFormatter::format_minor_code(14900, 'EUR'));
        $this->assertSame("149.125\u{00A0}TND", CurrencyFormatter::format_minor_code(149125, 'TND'));
    }

    public function test_purchase_presentation_uses_currency_minor_units(): void {
        force_current_language('en');

        $this->assertSame(CurrencyFormatter::format_minor_code(4490, 'JPY'), CommercePurchasePresentation::money(4490, 'JPY'));
        $this->assertSame(CurrencyFormatter::format_minor_code(4490, 'EUR'), CommercePurchasePresentation::money(4490, 'EUR'));
        $this->assertSame(CurrencyFormatter::format_minor_code(44900, 'TND'), CommercePurchasePresentation::money(44900, 'TND'));
    }

    public function test_personal_offer_cards_use_central_currency_metadata(): void {
        force_current_language('en');

        $presentation = CommercePersonalOfferPricingPresentationBuilder::build([
            'JPY' => ['regularminor' => 5500, 'offerminor' => 4490],
            'AED' => ['regularminor' => 5500, 'offerminor' => 4500],
        ], 'JPY');

        $this->assertSame('JPY', $presentation['currency']);
        $this->assertSame(CurrencyFormatter::format_minor(4490, 'JPY'), $presentation['offerformatted']);
        $this->assertStringNotContainsString('.', $presentation['offerformatted']);

        $cards = array_column($presentation['prices'], null, 'currency');
        $this->assertSame('🇯🇵', $cards['JPY']['flag']);
        $this->assertSame('🇦🇪', $cards['AED']['flag']);
        $this->assertSame(CurrencyFormatter::format_minor(4500, 'AED'), $cards['AED']['offerformatted']);
    }
}
