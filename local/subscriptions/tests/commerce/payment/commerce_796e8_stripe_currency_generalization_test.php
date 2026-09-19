<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\provider\stripe\StripePaymentProviderConfiguration;
use local_subscriptions\commerce\payment\provider\stripe\StripeSupportedCurrencies;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e8_stripe_currency_generalization_test extends advanced_testcase {
    public function test_stripe_is_no_longer_limited_to_four_historic_currencies(): void {
        $currencies = (new StripePaymentProviderConfiguration(true))
            ->get_currencies();

        foreach ([
            'EUR',
            'USD',
            'GBP',
            'CHF',
            'CAD',
            'AUD',
            'JPY',
            'AED',
            'PLN',
        ] as $currency) {
            $this->assertContains($currency, $currencies);
        }

        $this->assertGreaterThan(4, count($currencies));
    }

    public function test_restricted_ru_by_route_is_not_advertised_by_stripe(): void {
        $this->assertFalse(
            StripeSupportedCurrencies::supports('RUB')
        );
        $this->assertFalse(
            StripeSupportedCurrencies::supports('BYN')
        );
    }

    public function test_legacy_stripe_gateway_uses_same_currency_contract(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/stripe/StripeGateway.php'
        );

        $this->assertStringContainsString(
            'StripeSupportedCurrencies::supports',
            $contents
        );
        $this->assertStringContainsString(
            'StripeSupportedCurrencies::all()',
            $contents
        );
        $this->assertStringNotContainsString(
            "['EUR','USD','GBP','CHF']",
            $contents
        );
    }
}
