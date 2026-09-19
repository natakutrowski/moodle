<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g5_klarna_market_eligibility_test extends advanced_testcase {
    public function test_klarna_is_available_for_supported_france_euro_market(): void {
        $this->assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                'FR'
            )
        );
    }

    public function test_klarna_is_not_available_for_russia_or_rub(): void {
        $this->assertFalse(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                'RU'
            )
        );

        $this->assertFalse(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'RUB',
                'FR'
            )
        );
    }

    public function test_currency_only_architecture_inspection_keeps_market_potential(): void {
        $this->assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                null
            )
        );

        $this->assertFalse(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'JPY',
                null
            )
        );
    }

    public function test_non_market_dependent_methods_are_not_restricted_here(): void {
        $this->assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::CARD,
                'JPY',
                'JP'
            )
        );
    }
}
