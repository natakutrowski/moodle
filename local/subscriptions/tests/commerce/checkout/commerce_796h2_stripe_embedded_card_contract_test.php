<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h2_stripe_embedded_card_contract_test extends advanced_testcase {
    public function test_stripe_provider_uses_payment_intent_for_embedded_card(): void {
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
            'create_payment_intent(',
            $provider
        );
        $this->assertStringContainsString(
            '\\Stripe\\PaymentIntent::create(',
            $gateway
        );
        $this->assertStringContainsString(
            "'payment_method_types' =>",
            $gateway
        );
        $this->assertStringContainsString(
            "'client_secret'",
            $gateway
        );
    }

    public function test_subscription_mode_falls_back_to_hosted_checkout(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );

        $this->assertStringContainsString(
            "\$context->get_mode() !== 'payment'",
            $gateway
        );
        $this->assertStringContainsString(
            'return $this->create_checkout_session(',
            $gateway
        );
    }
}
