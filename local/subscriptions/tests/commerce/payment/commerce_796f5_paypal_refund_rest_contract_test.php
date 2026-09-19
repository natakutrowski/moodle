<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f5_paypal_refund_rest_contract_test extends \advanced_testcase {

    public function test_gateway_refund_uses_minor_to_major_currency_conversion_without_hardcoded_divisor(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalRestPaymentGateway.php'
        );
        self::assertIsString($gateway);

        self::assertStringContainsString('CommerceCurrencyAmount::major_input_from_minor(', $gateway);
        self::assertStringContainsString("'/v2/payments/captures/'", $gateway);
        self::assertStringContainsString(". '/refund'", $gateway);
        self::assertStringNotContainsString('/ 100', $gateway);
    }


    public function test_provider_refund_history_does_not_invent_refund_ids_from_capture_state(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalCommercePaymentProvider.php'
        );
        self::assertIsString($provider);

        self::assertStringContainsString('public function list_refunds(', $provider);
        self::assertStringContainsString('return [];', $provider);
        self::assertStringContainsString('Exact external refunds are', $provider);
        self::assertStringContainsString('imported from signed webhook resources', $provider);
    }


    public function test_individual_refund_retrieval_endpoint_remains_supported(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalRestPaymentGateway.php'
        );
        self::assertIsString($gateway);

        self::assertStringContainsString('public function retrieve_refund(', $gateway);
        self::assertStringContainsString("'/v2/payments/refunds/'", $gateway);
    }

}
