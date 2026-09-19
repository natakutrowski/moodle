<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\policy\CommercePaymentMarketSignalPolicy;

final class commerce_796h1323_market_signal_vpn_resilience_test extends \advanced_testcase {
    /**
     * @dataProvider rub_native_method_provider
     */
    public function test_rub_native_methods_do_not_depend_on_detected_country(
        string $method
    ): void {
        foreach (['RU', 'FR', 'DE', 'US', 'ZZ'] as $country) {
            self::assertTrue(
                CommercePaymentMethodMarketEligibility::supports(
                    $method,
                    'RUB',
                    $country
                ),
                $method . ' should remain RUB-eligible behind VPN country ' . $country
            );
        }
    }

    public static function rub_native_method_provider(): array {
        return [
            [CommercePaymentMethod::ALFA_PAY],
            [CommercePaymentMethod::SBP],
            [CommercePaymentMethod::SBERPAY],
            [CommercePaymentMethod::MIR_PAY],
        ];
    }

    /**
     * @dataProvider rub_native_method_provider
     */
    public function test_rub_native_methods_never_leak_into_non_rub_currency(
        string $method
    ): void {
        foreach (['EUR', 'USD', 'GBP'] as $currency) {
            self::assertFalse(
                CommercePaymentMethodMarketEligibility::supports(
                    $method,
                    $currency,
                    'RU'
                )
            );
        }
    }

    public function test_klarna_keeps_real_country_eligibility(): void {
        self::assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                'FR'
            )
        );
        self::assertFalse(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                'RU'
            )
        );
    }

    public function test_market_signal_policy_marks_only_real_market_constraint_as_authoritative(): void {
        $policy = new CommercePaymentMarketSignalPolicy();

        self::assertTrue(
            $policy->country_is_authoritative_for(
                CommercePaymentMethod::KLARNA,
                'EUR'
            )
        );

        foreach ([
            CommercePaymentMethod::CARD,
            CommercePaymentMethod::APPLE_PAY,
            CommercePaymentMethod::GOOGLE_PAY,
            CommercePaymentMethod::PAYPAL,
            CommercePaymentMethod::LINK,
            CommercePaymentMethod::ALFA_PAY,
            CommercePaymentMethod::SBP,
        ] as $method) {
            self::assertFalse(
                $policy->country_is_authoritative_for(
                    $method,
                    $method === CommercePaymentMethod::ALFA_PAY
                        || $method === CommercePaymentMethod::SBP
                        ? 'RUB'
                        : 'EUR'
                )
            );
        }
    }

    public function test_source_documents_vpn_resilience_rule(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/availability/'
            . 'CommercePaymentMethodMarketEligibility.php'
        );

        self::assertStringContainsString(
            'VPN/proxy',
            $source
        );
        self::assertStringContainsString(
            'country_is_authoritative_for',
            $source
        );
    }
}
