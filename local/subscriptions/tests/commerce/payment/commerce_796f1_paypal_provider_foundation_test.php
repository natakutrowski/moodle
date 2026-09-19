<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f1_paypal_provider_foundation_test extends \advanced_testcase {

    public function test_paypal_provider_declares_orders_v2_and_paypal_method(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalCommercePaymentProvider.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString("'integration' => 'orders_v2'", $source);
        self::assertStringContainsString('CommercePaymentMethod::PAYPAL', $source);
        self::assertStringContainsString("'checkout_mode' => 'paypal_checkout'", $source);
    }


    public function test_paypal_is_now_certified_for_refund_and_refund_history(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalCommercePaymentProvider.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('CommerceRefundCapablePaymentProvider', $source);
        self::assertStringContainsString('CommerceRefundHistoryCapablePaymentProvider', $source);
        self::assertStringContainsString('public function refund(', $source);
        self::assertStringContainsString('public function list_refunds(', $source);
    }

}
