<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodDefinition;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g1_payment_method_catalogue_test extends advanced_testcase {
    public function test_catalogue_contains_current_and_planned_methods(): void {
        $this->assertSame(
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::PAYPAL,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::KLARNA,
                CommercePaymentMethod::ALFA_PAY,
                CommercePaymentMethod::SBP,
                CommercePaymentMethod::SBERPAY,
                CommercePaymentMethod::MIR_PAY,
            ],
            CommercePaymentMethodCatalogue::keys()
        );
    }

    public function test_wallet_and_bnpl_semantics_are_provider_independent(): void {
        $this->assertTrue(
            CommercePaymentMethodCatalogue::get(
                CommercePaymentMethod::APPLE_PAY
            )->is_wallet()
        );
        $this->assertTrue(
            CommercePaymentMethodCatalogue::get(
                CommercePaymentMethod::GOOGLE_PAY
            )->is_wallet()
        );
        $this->assertTrue(
            CommercePaymentMethodCatalogue::get(
                CommercePaymentMethod::LINK
            )->is_wallet()
        );
        $this->assertSame(
            CommercePaymentMethodDefinition::FAMILY_BNPL,
            CommercePaymentMethodCatalogue::get(
                CommercePaymentMethod::KLARNA
            )->get_family()
        );
    }
}
