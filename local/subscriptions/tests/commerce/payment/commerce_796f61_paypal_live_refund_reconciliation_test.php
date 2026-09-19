<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f61_paypal_live_refund_reconciliation_test extends advanced_testcase {
    public function test_reconciliation_reads_each_known_refund_live_from_paypal(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/reconciliation/paypal/'
            . 'PayPalPaymentReconciliationService.php'
        );
        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalRestPaymentGateway.php'
        );

        $this->assertStringContainsString(
            'live_refunded_total_minor(',
            $service
        );
        $this->assertStringContainsString(
            'retrieve_refund(',
            $service
        );
        $this->assertStringContainsString(
            '/v2/payments/refunds/',
            $gateway
        );
        $this->assertStringNotContainsString(
            "'total_refunded_amount'",
            $service
        );
    }

    public function test_refunded_webhook_imports_exact_external_refund(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/'
            . 'PayPalWebhookService.php'
        );

        $this->assertStringContainsString(
            'new CommercePaymentRefundResult(',
            $contents
        );
        $this->assertStringContainsString(
            "'paypal_webhook'",
            $contents
        );
        $this->assertStringContainsString(
            'import_provider_refund(',
            $contents
        );
        $this->assertStringContainsString(
            "\$resource['amount']['value']",
            $contents
        );
    }
}
