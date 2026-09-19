<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h2_stripe_payment_intent_events_test extends advanced_testcase {
    public function test_legacy_webhook_parser_supports_payment_intent_events(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/stripe/StripeGateway.php'
        );

        $this->assertStringContainsString(
            "case 'payment_intent.succeeded':",
            $contents
        );
        $this->assertStringContainsString(
            "case 'payment_intent.payment_failed':",
            $contents
        );
        $this->assertStringContainsString(
            "new InternalEvent('checkout_completed'",
            $contents
        );
    }

    public function test_reconciliation_probe_supports_payment_intent_ids(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/reconciliation/stripe/'
            . 'StripeCheckoutSessionStatusProbe.php'
        );

        $this->assertStringContainsString(
            "str_starts_with(\$sessionid, 'pi_')",
            $contents
        );
        $this->assertStringContainsString(
            '\\Stripe\\PaymentIntent::retrieve(',
            $contents
        );
    }
}
