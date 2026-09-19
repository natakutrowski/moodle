<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f4_paypal_webhook_processing_test extends advanced_testcase {
    public function test_approved_and_completed_events_use_existing_secure_capture_service(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/'
            . 'PayPalWebhookService.php'
        );

        $this->assertStringContainsString(
            "'CHECKOUT.ORDER.APPROVED'",
            $contents
        );
        $this->assertStringContainsString(
            "'PAYMENT.CAPTURE.COMPLETED'",
            $contents
        );
        $this->assertStringContainsString(
            'PayPalReturnCaptureService',
            $contents
        );
        $this->assertStringContainsString(
            '$this->capture->capture(',
            $contents
        );
    }

    public function test_pending_events_never_trigger_fulfillment(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/'
            . 'PayPalWebhookService.php'
        );

        $this->assertStringContainsString(
            "'PAYMENT.CAPTURE.PENDING'",
            $contents
        );
        $this->assertStringContainsString(
            "'acknowledged_pending'",
            $contents
        );
    }

    public function test_denied_capture_can_mark_matching_native_payment_failed(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/'
            . 'PayPalWebhookService.php'
        );

        $this->assertStringContainsString(
            "'PAYMENT.CAPTURE.DENIED'",
            $contents
        );
        $this->assertStringContainsString(
            "new InternalEvent(",
            $contents
        );
        $this->assertStringContainsString(
            "'payment_failed'",
            $contents
        );
        $this->assertStringContainsString(
            'EventRouter::handle(',
            $contents
        );
    }
}
