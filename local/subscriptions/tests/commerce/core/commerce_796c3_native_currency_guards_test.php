<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmInput;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferPricingPresentationBuilder;

/** Tests 7.96C3 removal of native EUR/RUB currency guards. */
final class commerce_796c3_native_currency_guards_test extends advanced_testcase {
    public function test_personal_offer_major_amounts_use_currency_minor_units(): void {
        self::assertSame(
            'EUR:3990,JPY:4490,TND:149125',
            CommercePersonalOfferCrmInput::amounts_from_major([
                'EUR' => '39.90',
                'JPY' => '4490',
                'TND' => '149.125',
            ])
        );
    }

    public function test_personal_offer_pricing_builder_accepts_any_known_currency(): void {
        $presentation = CommercePersonalOfferPricingPresentationBuilder::build([
            'JPY' => ['regularminor' => 5490, 'offerminor' => 4490],
            'TND' => ['regularminor' => 179125, 'offerminor' => 149125],
        ], 'TND');

        self::assertSame('TND', $presentation['currency']);
        self::assertSame(149125, $presentation['offerminor']);
        self::assertSame(['JPY', 'TND'], array_column($presentation['prices'], 'currency'));
    }

    public function test_express_checkout_uses_registry_and_provider_currency_capabilities(): void {
        $path = __DIR__ . '/../../../classes/commerce/checkout/express/CommerceCheckoutExpressService.php';
        $source = file_get_contents($path);
        self::assertIsString($source);
        self::assertStringContainsString('CommerceCurrencyRegistry', $source);
        self::assertStringContainsString('supports_currency($currency)', $source);
        self::assertStringNotContainsString("in_array(\$currency, ['EUR', 'RUB']", $source);
        self::assertStringNotContainsString("=== 'RUB' ? 'alfa' : 'stripe'", $source);
    }
}
