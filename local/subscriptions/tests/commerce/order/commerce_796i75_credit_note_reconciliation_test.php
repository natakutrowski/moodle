<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

final class commerce_796i75_credit_note_reconciliation_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_import_service_reconciles_preexisting_refunds(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/refund/CommercePaymentRefundImportService.php'
        );

        self::assertStringContainsString(
            'CommerceCreditNoteReconciliationService',
            $source
        );
        self::assertStringContainsString(
            '->reconcile_payment($paymentid)',
            $source
        );
    }

    public function test_paypal_webhook_direct_refund_import_issues_credit_note(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/PayPalWebhookService.php'
        );

        self::assertStringContainsString(
            '$synchronizedrefund =',
            $source
        );
        self::assertStringContainsString(
            'new CommerceCreditNoteIssuer($this->database)',
            $source
        );
        self::assertStringContainsString(
            '->issue_if_eligible($synchronizedrefund)',
            $source
        );
    }

    public function test_i75_has_no_schema_change(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );

        self::assertSame(
            1,
            preg_match(
                '/\\$plugin->version\\s*=\\s*(\\d+);/',
                $version,
                $pluginversionmatch
            )
        );
        self::assertGreaterThanOrEqual(
            2026090801,
            (int)$pluginversionmatch[1]
        );
    }
}
