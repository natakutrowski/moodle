<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f3_paypal_return_capture_test extends advanced_testcase {
    public function test_return_endpoint_routes_paypal_to_capture_service(): void {
        global $CFG;
        $contents = file_get_contents($CFG->dirroot . '/local/subscriptions/payment/return.php');
        $this->assertStringContainsString('Provider::PAYPAL', $contents);
        $this->assertStringContainsString('PayPalReturnCaptureService::create($DB)', $contents);
        $this->assertStringContainsString("optional_param('token'", $contents);
        $this->assertStringContainsString("'code' => 'paypal_capture'", $contents);
    }

    public function test_capture_service_verifies_provider_amount_currency_and_finalizes_through_event_router(): void {
        global $CFG;
        $contents = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/PayPalReturnCaptureService.php'
        );
        foreach ([
            'hash_equals($orderid, trim($returnedorderid))',
            "'APPROVED'",
            "'COMPLETED'",
            'CommerceCurrencyAmount::from_major_input',
            'get_amount_minor() !== $attempt->get_amount_minor()',
            '$currency !== $attempt->get_currency()',
            "new InternalEvent('checkout_completed'",
            'EventRouter::handle($event)',
            "'paypal_capture_id'",
        ] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }
    }

    public function test_f3_enables_paypal_only_after_return_capture_exists(): void {
        global $CFG;
        $factory = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/CommercePaymentProviderRegistryFactory.php'
        );
        $this->assertStringContainsString('new PayPalRestPaymentGateway', $factory);
        $this->assertMatchesRegularExpression(
            '/new PayPalPaymentProviderConfiguration\\(\\s*true\\s*\\)/',
            $factory
        );
    }
}
