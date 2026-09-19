<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h3_stripe_wallet_contract_test extends advanced_testcase {
    public function test_wallets_use_card_payment_intent_rail(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'StripeCommercePaymentProvider.php'
        );
        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentMethod::APPLE_PAY',
            $provider
        );
        $this->assertStringContainsString(
            'CommercePaymentMethod::GOOGLE_PAY',
            $provider
        );
        $this->assertStringContainsString(
            'create_payment_intent(',
            $provider
        );
        $this->assertStringContainsString(
            "'payment_method_types' =>",
            $gateway
        );
        $this->assertStringContainsString(
            "['card']",
            $gateway
        );
    }
}
