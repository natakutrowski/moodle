<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f32_paypal_return_service_boundary_test extends advanced_testcase {
    public function test_return_controller_uses_return_service_not_concrete_provider(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/payment/return.php'
        );

        $this->assertStringContainsString(
            'Provider::PAYPAL',
            $contents
        );
        $this->assertStringContainsString(
            'PayPalReturnCaptureService',
            $contents
        );
        $this->assertStringContainsString(
            '->capture(',
            $contents
        );
        $this->assertStringNotContainsString(
            'new PayPalCommercePaymentProvider',
            $contents
        );
    }
}
