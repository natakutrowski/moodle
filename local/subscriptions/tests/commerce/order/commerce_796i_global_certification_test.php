<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

/**
 * Global certification gate for Commerce 7.96I.
 *
 * This test locks the cross-phase production wiring introduced from I2 to I8.
 * Behavioural coverage remains in the dedicated I2-I8 tests; this gate verifies
 * that the major legal/invoicing/refund/admin contracts are still connected.
 */
final class commerce_796i_global_certification_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_v1_seller_routing_is_market_based_only(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/legal/merchant/CommerceMerchantResolver.php'
        );

        self::assertStringContainsString(
            "in_array(\$country, ['RU', 'BY'], true)",
            $source
        );
        self::assertStringContainsString(
            'CommerceLegalEntityRegistry::RU_MAIN',
            $source
        );
        self::assertStringContainsString(
            'CommerceLegalEntityRegistry::FR_MAIN',
            $source
        );
        self::assertStringContainsString(
            'RULE_UNKNOWN_FALLBACK_FR',
            $source
        );
        self::assertStringNotContainsString(
            'get_currency()',
            $source
        );
        self::assertStringNotContainsString(
            'get_provider()',
            $source
        );
    }

    public function test_purchase_freezes_seller_and_legal_consent_before_payment(): void {
        global $CFG;

        $persister = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutPurchasePersister.php'
        );
        $consent = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/legal/document/CommerceLegalConsentSnapshot.php'
        );

        self::assertStringContainsString(
            'legal_entity_snapshot',
            $persister
        );
        self::assertStringContainsString(
            'legal_consent',
            $persister
        );
        self::assertStringContainsString(
            'legal_consent_v1',
            $consent
        );
        self::assertStringContainsString(
            'document_set',
            $consent
        );
        self::assertStringContainsString(
            'accepted_at',
            $consent
        );
    }

    public function test_legal_documents_are_versioned_and_market_resolved(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/legal/document/CommerceLegalDocumentResolver.php'
        );

        self::assertStringContainsString(
            "in_array(\$country, ['RU', 'BY'], true)",
            $resolver
        );
        self::assertStringContainsString(
            "'legal_documents_' . \$profile . '_version'",
            $resolver
        );
        self::assertStringContainsString(
            "'policy_url_' . \$profile",
            $resolver
        );
        self::assertStringContainsString(
            "'terms_url_' . \$profile",
            $resolver
        );
        self::assertStringContainsString(
            "'offer_url_' . \$profile",
            $resolver
        );
    }

    public function test_invoice_identity_is_immutable_sequential_and_tax_neutral_v1(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/order/invoice/CommerceInvoiceIssuer.php'
        );

        self::assertStringContainsString(
            "TABLE_INVOICE = 'local_subs_commerce_invoice'",
            $source
        );
        self::assertStringContainsString(
            "TABLE_SEQUENCE = 'local_subs_commerce_inv_seq'",
            $source
        );
        self::assertStringContainsString(
            "'CFR-%s-%04d-%06d'",
            $source
        );
        self::assertStringContainsString(
            'legal_entity_snapshot',
            $source
        );
        self::assertStringContainsString(
            "'tax_status' => 'not_modelled_v1'",
            $source
        );
    }

    public function test_payment_method_remains_distinct_from_provider(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/method/CommercePersistedPaymentMethodResolver.php'
        );

        self::assertStringContainsString(
            "['payment_method']",
            $resolver
        );
        self::assertStringContainsString(
            "if (\$provider === 'paypal')",
            $resolver
        );
        self::assertStringNotContainsString(
            "\$provider === 'stripe' ?",
            $resolver
        );
        self::assertStringNotContainsString(
            "\$provider === 'alfa' ?",
            $resolver
        );
    }

    public function test_successful_refunds_are_credit_note_aware_on_all_production_paths(): void {
        global $CFG;

        $command = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/refund/CommercePaymentRefundCommand.php'
        );
        $import = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/refund/CommercePaymentRefundImportService.php'
        );
        $paypal = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/PayPalWebhookService.php'
        );

        self::assertStringContainsString(
            'issue_if_eligible',
            $command
        );
        self::assertStringContainsString(
            'CommerceCreditNoteReconciliationService',
            $import
        );
        self::assertStringContainsString(
            'issue_if_eligible',
            $import
        );
        self::assertStringContainsString(
            'CommerceCreditNoteIssuer',
            $paypal
        );
        self::assertStringContainsString(
            'issue_if_eligible',
            $paypal
        );
    }

    public function test_credit_notes_inherit_issued_invoice_identity_and_have_independent_sequence(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/order/creditnote/CommerceCreditNoteIssuer.php'
        );

        self::assertStringContainsString(
            'CommerceInvoiceIssuer',
            $source
        );
        self::assertStringContainsString(
            "TABLE_SEQUENCE = 'local_subs_commerce_cn_seq'",
            $source
        );
        self::assertStringContainsString(
            "'CFR-%s-A-%04d-%06d'",
            $source
        );
        self::assertStringContainsString(
            'invoice_number',
            $source
        );
        self::assertStringContainsString(
            "'tax_status' => 'not_modelled_v1'",
            $source
        );
    }

    public function test_admin_ux_exposes_documents_and_historical_seller_without_legacy_write_through(): void {
        global $CFG;

        $purchaseview = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/purchases/view.php'
        );
        $configuration = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        self::assertStringContainsString(
            'commerce_documents_title',
            $purchaseview
        );
        self::assertStringContainsString(
            'commerce_purchase_seller_snapshot_title',
            $purchaseview
        );
        self::assertStringContainsString(
            "'mt-3 mb-3'",
            $purchaseview
        );
        self::assertStringNotContainsString(
            'snapshot vendeur I4',
            $purchaseview
        );
        self::assertStringNotContainsString(
            'set_config($legacyprefix . $legacyfield',
            $configuration
        );
        self::assertStringContainsString(
            'read-only compatibility',
            $configuration
        );
    }

    public function test_global_i_certification_requires_no_new_schema_change(): void {
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
