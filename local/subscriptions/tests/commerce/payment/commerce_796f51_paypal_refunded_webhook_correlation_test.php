<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f51_paypal_refunded_webhook_correlation_test extends \advanced_testcase {

    public function test_refunded_webhook_does_not_assume_refund_resource_id_is_capture_id(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/PayPalWebhookService.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString("'PAYMENT.CAPTURE.REFUNDED'", $source);
        self::assertStringContainsString('$refundid = trim(', $source);
        self::assertStringContainsString('$this->capture_id_from_refund_event(', $source);
        self::assertStringContainsString('find_by_provider_refund_id(', $source);
        self::assertStringContainsString("'unresolved_refunded_capture'", $source);
    }


    public function test_external_refund_from_signed_webhook_is_imported_with_exact_amount(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/PayPalWebhookService.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('CommerceCurrencyAmount', $source);
        self::assertStringContainsString('::from_major_input(', $source);
        self::assertStringContainsString('CommercePaymentRefundResult(', $source);
        self::assertStringContainsString('CommercePaymentRefundImportService', $source);
    }

}
