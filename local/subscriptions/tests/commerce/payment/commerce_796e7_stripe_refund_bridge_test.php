<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e7_stripe_refund_bridge_test extends advanced_testcase {
    public function test_legacy_stripe_gateway_supports_session_intent_and_charge_refunds(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/stripe/StripeGateway.php'
        );

        $this->assertStringContainsString(
            'RefundGatewayInterface',
            $contents
        );
        $this->assertStringContainsString(
            "str_starts_with(\$providerpaymentid, 'cs_')",
            $contents
        );
        $this->assertStringContainsString(
            "str_starts_with(\$providerpaymentid, 'pi_')",
            $contents
        );
        $this->assertStringContainsString(
            "str_starts_with(\$providerpaymentid, 'ch_')",
            $contents
        );
        $this->assertStringContainsString(
            '\\Stripe\\Refund::create(',
            $contents
        );
        $this->assertStringContainsString(
            "'idempotency_key'",
            $contents
        );
    }

    public function test_bridge_audit_requires_refund_contract_for_advertised_capability(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/audit/'
            . 'audit_commerce_payment_bridges.php'
        );

        $this->assertStringContainsString(
            'CommerceRefundCapablePaymentProvider',
            $contents
        );
        $this->assertStringNotContainsString(
            'incorrectly announces refund support',
            $contents
        );
    }

    public function test_alfa_is_now_certified_for_refund_and_refund_history(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/AlfaCommercePaymentProvider.php'
        );
        $this->assertIsString($contents);
        $this->assertStringContainsString(
            'CommerceRefundCapablePaymentProvider',
            $contents
        );
        $this->assertStringContainsString(
            'CommerceRefundHistoryCapablePaymentProvider',
            $contents
        );
        $this->assertStringContainsString(
            '// Certified through CommerceRefundCapablePaymentProvider.',
            $contents
        );
    }
}
