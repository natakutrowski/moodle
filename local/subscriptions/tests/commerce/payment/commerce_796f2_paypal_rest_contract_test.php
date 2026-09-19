<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f2_paypal_rest_contract_test extends advanced_testcase {
    public function test_rest_gateway_uses_oauth_orders_v2_and_paypal_request_id(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalRestPaymentGateway.php'
        );

        foreach ([
            '/v1/oauth2/token',
            '/v2/checkout/orders',
            '/capture',
            'PayPal-Request-Id:',
            'Authorization: Basic ',
            'Authorization: Bearer ',
            "'intent' => 'CAPTURE'",
            "'user_action' => 'PAY_NOW'",
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }
    }

    public function test_rest_gateway_uses_central_currency_minor_unit_conversion(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalRestPaymentGateway.php'
        );

        $this->assertStringContainsString(
            'CommerceCurrencyAmount::major_input_from_minor',
            $contents
        );

        $this->assertStringNotContainsString(
            '/ 100',
            $contents
        );
    }

    public function test_f3_enables_paypal_routing_after_return_capture_exists(): void {
        global $CFG;

        $factory = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentProviderRegistryFactory.php'
        );
        $returnhandler = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/payment/return.php'
        );

        $this->assertStringContainsString(
            'new PayPalPaymentProviderConfiguration(',
            $factory
        );
        $this->assertStringContainsString(
            'new PayPalPaymentProviderConfiguration(' . PHP_EOL
            . '                    true',
            $factory
        );
        $this->assertStringContainsString(
            'Provider::PAYPAL',
            $returnhandler
        );
        $this->assertStringContainsString(
            'PayPalReturnCaptureService',
            $returnhandler
        );
        $this->assertStringContainsString(
            '->capture(',
            $returnhandler
        );
    }
}
