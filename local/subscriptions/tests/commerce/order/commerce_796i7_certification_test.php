<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

/**
 * 7.96I7 certification gate.
 *
 * This test deliberately verifies the production wiring around the lower-level
 * behavioural tests from I7.2-I7.5: admin refund, provider synchronization,
 * PayPal webhook, immutable credit-note documents and customer/admin exposure.
 */
final class commerce_796i7_certification_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_admin_refund_path_uses_native_refund_command(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/purchases/refund.php'
        );

        self::assertStringContainsString(
            'CommercePaymentRefundCommand',
            $source
        );
        self::assertStringContainsString(
            '->execute(',
            $source
        );
    }

    public function test_manual_provider_sync_uses_document_aware_import_service(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/purchases/sync_refunds.php'
        );
        $import = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/refund/CommercePaymentRefundImportService.php'
        );

        self::assertStringContainsString(
            'CommercePaymentRefundImportService',
            $source
        );
        self::assertStringContainsString(
            'CommerceCreditNoteReconciliationService',
            $import
        );
        self::assertStringContainsString(
            '->issue_if_eligible($synchronized)',
            $import
        );
        self::assertStringContainsString(
            '->reconcile_payment($paymentid)',
            $import
        );
    }

    public function test_paypal_direct_webhook_refund_is_document_aware(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/PayPalWebhookService.php'
        );

        self::assertStringContainsString(
            '->import_provider_refund(',
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

    public function test_credit_note_ledger_has_one_document_per_refund_and_separate_sequence(): void {
        global $CFG;

        $installxml = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/db/install.xml'
        );
        $issuer = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/order/creditnote/CommerceCreditNoteIssuer.php'
        );

        self::assertMatchesRegularExpression(
            '/<TABLE NAME="local_subs_commerce_credit_note".*?'
                . '<KEY NAME="refund_fk" TYPE="foreign-unique".*?'
                . 'TABLE="local_subscriptions_commerce_refund"/s',
            $installxml
        );
        self::assertStringContainsString(
            "public const TABLE_SEQUENCE = 'local_subs_commerce_cn_seq';",
            $issuer
        );
        self::assertStringContainsString(
            "'CFR-%s-A-%04d-%06d'",
            $issuer
        );
        self::assertStringContainsString(
            "'tax_status' => 'not_modelled_v1'",
            $issuer
        );
    }

    public function test_credit_note_identity_is_inherited_from_issued_invoice(): void {
        global $CFG;

        $issuer = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/order/creditnote/CommerceCreditNoteIssuer.php'
        );

        self::assertStringContainsString(
            'CommerceInvoiceIssuer',
            $issuer
        );
        self::assertStringContainsString(
            "'sellerjson' => \$this->encode(\$invoice->seller)",
            $issuer
        );
        self::assertStringContainsString(
            "'customerjson' => \$this->encode(\$invoice->customer)",
            $issuer
        );
    }

    public function test_customer_and_admin_document_surfaces_are_wired(): void {
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_credit_note.php'
        );
        $orderdetails = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_details.php'
        );
        $purchaseview = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        self::assertStringContainsString(
            'CommerceOrderPresentationService::create()->find_for_user',
            $endpoint
        );
        self::assertStringContainsString(
            '$note->purchaseid !== $order->purchaseid',
            $endpoint
        );
        self::assertStringContainsString(
            'credit_notes_for_purchase',
            $orderdetails
        );
        self::assertStringContainsString(
            'credit_notes_for_purchase',
            $purchaseview
        );
        self::assertStringContainsString(
            'CommerceRefundReasonPresenter',
            $purchaseview
        );
    }

    public function test_i7_certification_requires_no_additional_schema_change(): void {
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
