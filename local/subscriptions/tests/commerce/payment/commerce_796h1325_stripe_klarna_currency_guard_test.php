<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;

final class commerce_796h1325_stripe_klarna_currency_guard_test extends \advanced_testcase {
    /**
     * @dataProvider supported_klarna_currency_provider
     */
    public function test_current_stripe_klarna_rail_accepts_supported_currency(
        string $currency,
        string $country
    ): void {
        self::assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                $currency,
                $country
            )
        );
    }

    public static function supported_klarna_currency_provider(): array {
        return [
            ['EUR', 'FR'],
            ['GBP', 'GB'],
            ['CHF', 'CH'],
            ['DKK', 'DK'],
            ['NOK', 'NO'],
            ['SEK', 'SE'],
            ['CZK', 'CZ'],
            ['RON', 'RO'],
            ['PLN', 'PL'],
        ];
    }

    /**
     * @dataProvider unsupported_klarna_currency_provider
     */
    public function test_current_stripe_klarna_rail_rejects_currency_before_stripe(
        string $currency
    ): void {
        self::assertFalse(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                $currency,
                'US'
            )
        );
    }

    public static function unsupported_klarna_currency_provider(): array {
        return [
            'USD' => ['USD'],
            'CAD' => ['CAD'],
            'AUD' => ['AUD'],
            'NZD' => ['NZD'],
            'RUB' => ['RUB'],
        ];
    }

    public function test_usd_guard_prevents_klarna_from_poisoning_express_checkout(): void {
        self::assertFalse(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'USD',
                'US'
            )
        );
    }

    public function test_source_documents_stripe_deferred_intent_constraint(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/availability/'
            . 'CommercePaymentMethodMarketEligibility.php'
        );

        self::assertStringContainsString(
            'deferred Express Checkout intent',
            $source
        );
        self::assertStringNotContainsString(
            "'USD',\n    ];",
            substr(
                $source,
                strpos($source, 'private const KLARNA_CURRENCIES'),
                1200
            )
        );
    }
}
