<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f31_paypal_routing_activation_test extends advanced_testcase {
    public function test_paypal_is_only_enabled_after_f3_return_capture_surface_exists(): void {
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
            'true',
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
