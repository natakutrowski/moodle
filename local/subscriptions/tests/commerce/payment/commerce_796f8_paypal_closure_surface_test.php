<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f8_paypal_closure_surface_test extends \advanced_testcase {

    public function test_paypal_integration_contains_all_current_certified_surfaces(): void {
        global $CFG;
        $required = [
            '/local/subscriptions/payment/return.php',
            '/local/subscriptions/webhook/paypal.php',
            '/local/subscriptions/admin/commerce/configuration/provider.php',
            '/local/subscriptions/admin/commerce/configuration/section.php',
            '/local/subscriptions/admin/commerce/purchases/reconcile_paypal.php',
            '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalCommercePaymentProvider.php',
            '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalRestPaymentGateway.php',
        ];
        foreach ($required as $relative) {
            self::assertFileExists($CFG->dirroot . $relative);
        }
        self::assertFileDoesNotExist(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/paypal.php'
        );
    }


    public function test_paypal_gateway_covers_orders_captures_refunds_and_verified_webhooks(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalRestPaymentGateway.php'
        );
        self::assertIsString($gateway);
        global $CFG;

        $webhook = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/PayPalWebhookSignatureVerifier.php'
        );
        self::assertIsString($webhook);

        foreach ([
            '/v2/checkout/orders',
            '/capture',
            '/v2/payments/captures/',
            '/refund',
            '/v2/payments/refunds/',
            'PayPal-Request-Id:',
        ] as $expected) {
            self::assertStringContainsString($expected, $gateway);
        }
        self::assertStringContainsString('/v1/notifications/verify-webhook-signature', $webhook);
        self::assertStringContainsString("'SUCCESS'", $webhook);
    }

}
