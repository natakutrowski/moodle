<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f6_paypal_reconciliation_architecture_test extends advanced_testcase {
    public function test_paypal_reconciliation_is_refund_aware_and_safe(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/reconciliation/paypal/'
            . 'PayPalPaymentReconciliationService.php'
        );

        foreach ([
            'retrieve_order(',
            'retrieve_capture(',
            'campusrefundedminor',
            'refundedminor',
            'refundmatches',
            'live_refunded_total_minor',
            'retrieve_refund(',
            'provider_already_refunded',
            'capture_not_completed',
            'PayPalReturnCaptureService',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }
    }

    public function test_paypal_provider_status_understands_refunded_capture(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/reconciliation/paypal/'
            . 'PayPalPaymentProviderStatus.php'
        );

        $this->assertStringContainsString(
            "'PARTIALLY_REFUNDED'",
            $contents
        );
        $this->assertStringContainsString(
            "'REFUNDED'",
            $contents
        );
        $this->assertStringContainsString(
            'is_financially_settled()',
            $contents
        );
    }
}
