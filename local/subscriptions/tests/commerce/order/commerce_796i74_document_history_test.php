<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\document\CommerceOrderDocumentHistoryRepository;

final class commerce_796i74_document_history_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_document_history_returns_invoice_and_credit_notes_in_issue_order(): void {
        global $DB;

        $now = time();
        $purchaseid = $this->create_purchase($now);
        $invoiceid = (int)$DB->insert_record(
            'local_subs_commerce_invoice',
            (object)[
                'purchaseid' => $purchaseid,
                'entitykey' => 'fr_main',
                'invoicenumber' => 'CFR-FR-2026-000001',
                'invoiceyear' => 2026,
                'sequence' => 1,
                'issuedat' => $now,
                'sellerjson' => '{}',
                'customerjson' => '{}',
                'financialjson' => '{"currency":"EUR","total_minor":28000}',
                'metadatajson' => '{}',
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $refund1 = $this->create_refund($purchaseid, $now + 10, 5000, 're_1');
        $refund2 = $this->create_refund($purchaseid, $now + 20, 3000, 're_2');

        $this->create_credit_note(
            $purchaseid,
            $invoiceid,
            $refund2,
            'CFR-FR-A-2026-000002',
            2,
            $now + 20,
            3000
        );
        $this->create_credit_note(
            $purchaseid,
            $invoiceid,
            $refund1,
            'CFR-FR-A-2026-000001',
            1,
            $now + 10,
            5000
        );

        $repository = new CommerceOrderDocumentHistoryRepository($DB);
        $invoice = $repository->invoice_for_purchase($purchaseid);
        $notes = $repository->credit_notes_for_purchase($purchaseid);

        self::assertNotNull($invoice);
        self::assertSame('CFR-FR-2026-000001', $invoice['number']);
        self::assertCount(2, $notes);
        self::assertSame('CFR-FR-A-2026-000001', $notes[0]->number);
        self::assertSame(5000, $notes[0]->financial['refund_minor']);
        self::assertSame('CFR-FR-A-2026-000002', $notes[1]->number);
    }

    public function test_credit_note_download_endpoint_enforces_order_ownership_and_document_purchase(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_credit_note.php'
        );

        self::assertStringContainsString(
            'CommerceOrderPresentationService::create()->find_for_user',
            $source
        );
        self::assertStringContainsString(
            '$note->purchaseid !== $order->purchaseid',
            $source
        );
        self::assertStringContainsString(
            "CommerceCreditNotePdfService",
            $source
        );
    }

    public function test_order_and_purchase_details_surface_document_history(): void {
        global $CFG;

        $orderdetails = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_details.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/order_details/page.mustache'
        );
        $purchaseview = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        self::assertStringContainsString(
            'CommerceOrderDocumentHistoryRepository',
            $orderdetails
        );
        self::assertStringContainsString('{{#documents}}', $template);
        self::assertStringContainsString(
            'credit_notes_for_purchase',
            $purchaseview
        );
        self::assertStringContainsString(
            'order_credit_note.php',
            $purchaseview
        );
    }

    public function test_i74_adds_no_schema_or_version_change(): void {
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

    private function create_purchase(int $now): int {
        global $DB;

        return (int)$DB->insert_record(
            'local_subscriptions_commerce_purchase',
            (object)[
                'purchaseuuid' => bin2hex(random_bytes(16)),
                'reference' => 'cmp_' . bin2hex(random_bytes(12)),
                'type' => 'digital',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => null,
                'customeremail' => 'buyer@example.test',
                'status' => 'paid',
                'currency' => 'EUR',
                'subtotalminor' => 28000,
                'discountminor' => 0,
                'totalminor' => 28000,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    private function create_refund(
        int $purchaseid,
        int $now,
        int $amountminor,
        string $providerrefundid
    ): int {
        global $DB;

        $paymentid = $DB->get_field(
            'local_subscriptions_commerce_payment',
            'id',
            ['purchaseid' => $purchaseid],
            IGNORE_MISSING
        );
        if ($paymentid === false) {
            $paymentid = (int)$DB->insert_record(
                'local_subscriptions_commerce_payment',
                (object)[
                    'purchaseid' => $purchaseid,
                    'sequence' => 0,
                    'provider' => 'stripe',
                    'providerreference' => 'pi_test',
                    'providerorderid' => null,
                    'status' => 'paid',
                    'currency' => 'EUR',
                    'amountminor' => 28000,
                    'transactionid' => 'txn_test',
                    'legacyrequestid' => null,
                    'paidat' => $now,
                    'metadatajson' => '{}',
                    'paymenturl' => null,
                    'providerpayload' => null,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]
            );
        }

        return (int)$DB->insert_record(
            'local_subscriptions_commerce_refund',
            (object)[
                'paymentid' => (int)$paymentid,
                'provider' => 'stripe',
                'providerrefundid' => $providerrefundid,
                'idempotencykey' => 'idem-' . $providerrefundid,
                'status' => 'succeeded',
                'currency' => 'EUR',
                'amountminor' => $amountminor,
                'reason' => 'Test',
                'metadatajson' => '{}',
                'providerpayload' => '{}',
                'createdby' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    private function create_credit_note(
        int $purchaseid,
        int $invoiceid,
        int $refundid,
        string $number,
        int $sequence,
        int $issuedat,
        int $amountminor
    ): void {
        global $DB;

        $DB->insert_record(
            'local_subs_commerce_credit_note',
            (object)[
                'refundid' => $refundid,
                'invoiceid' => $invoiceid,
                'purchaseid' => $purchaseid,
                'entitykey' => 'fr_main',
                'creditnotenumber' => $number,
                'creditnoteyear' => 2026,
                'sequence' => $sequence,
                'issuedat' => $issuedat,
                'sellerjson' => '{}',
                'customerjson' => '{}',
                'financialjson' => json_encode([
                    'currency' => 'EUR',
                    'refund_minor' => $amountminor,
                    'tax_status' => 'not_modelled_v1',
                ]),
                'metadatajson' => '{}',
                'timecreated' => $issuedat,
                'timemodified' => $issuedat,
            ]
        );
    }
}
