<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f5_paypal_refund_webhook_test extends advanced_testcase {
    public function test_refunded_capture_triggers_native_refund_sync(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/'
            . 'PayPalWebhookService.php'
        );

        $this->assertStringContainsString(
            "'PAYMENT.CAPTURE.REFUNDED'",
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentRefundImportService',
            $contents
        );
        $this->assertStringContainsString(
            'import_for_payment(',
            $contents
        );
        $this->assertStringContainsString(
            "'refunds_synchronized'",
            $contents
        );
        $this->assertStringContainsString(
            'capture_id_from_refund_event(',
            $contents
        );
        $this->assertStringContainsString(
            'find_by_provider_refund_id(',
            $contents
        );
    }

    public function test_pending_and_failed_refund_events_are_explicitly_handled(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/'
            . 'PayPalWebhookService.php'
        );

        $this->assertStringContainsString(
            "'PAYMENT.REFUND.PENDING'",
            $contents
        );
        $this->assertStringContainsString(
            "'PAYMENT.REFUND.FAILED'",
            $contents
        );
        $this->assertStringContainsString(
            'refund_marked_failed',
            $contents
        );
    }
}
