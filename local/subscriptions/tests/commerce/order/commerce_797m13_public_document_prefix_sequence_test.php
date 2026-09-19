<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\creditnote\CommerceCreditNoteIssuer;
use local_subscriptions\commerce\order\invoice\CommerceInvoiceIssuer;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;

/** Public document numbering must be shared by entity keys using the same visible prefix. */
final class commerce_797m13_public_document_prefix_sequence_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_invoice_fr_legacy_and_fr_main_share_public_sequence(): void {
        global $DB;

        $year = (int)userdate(time(), '%Y');
        $this->insert_existing_invoice(
            'fr_legacy',
            $year,
            1,
            'CFR-FR-' . $year . '-000001'
        );

        self::assertFalse($DB->record_exists(
            CommerceInvoiceIssuer::TABLE_INVOICE,
            ['entitykey' => 'fr_main', 'invoiceyear' => $year]
        ));
        self::assertFalse($DB->record_exists(
            CommerceInvoiceIssuer::TABLE_SEQUENCE,
            ['entitykey' => 'fr_main', 'invoiceyear' => $year]
        ));

        $purchaseid = $this->purchase('cmp_' . bin2hex(random_bytes(12)));
        $invoice = (new CommerceInvoiceIssuer($DB))->get_or_issue(
            $this->order($purchaseid, 'fr_main', 'CampusFR France')
        );

        self::assertSame(2, $invoice->sequence);
        self::assertSame('CFR-FR-' . $year . '-000002', $invoice->number);
    }

    public function test_invoice_uses_highest_counter_across_same_public_prefix(): void {
        global $DB;

        $year = (int)userdate(time(), '%Y');
        $DB->insert_record(CommerceInvoiceIssuer::TABLE_SEQUENCE, (object)[
            'entitykey' => 'fr_legacy',
            'invoiceyear' => $year,
            'lastsequence' => 7,
            'timemodified' => time(),
        ]);

        $purchaseid = $this->purchase('cmp_' . bin2hex(random_bytes(12)));
        $invoice = (new CommerceInvoiceIssuer($DB))->get_or_issue(
            $this->order($purchaseid, 'fr_main', 'CampusFR France')
        );

        self::assertSame(8, $invoice->sequence);
        self::assertSame('CFR-FR-' . $year . '-000008', $invoice->number);
    }

    public function test_credit_notes_fr_legacy_and_fr_main_share_public_sequence(): void {
        global $DB;

        [, $legacypaymentid] = $this->create_paid_purchase(
            'fr_legacy',
            'Legacy FR Seller',
            'EUR',
            10000
        );
        [, $mainpaymentid] = $this->create_paid_purchase(
            'fr_main',
            'Current FR Seller',
            'EUR',
            10000
        );

        $refunds = new CommercePaymentRefundRepository($DB);
        $legacyrefund = $this->successful_refund(
            $refunds,
            $legacypaymentid,
            'legacy-fr-refund',
            're_legacy'
        );
        $mainrefund = $this->successful_refund(
            $refunds,
            $mainpaymentid,
            'main-fr-refund',
            're_main'
        );

        $issuer = new CommerceCreditNoteIssuer($DB);
        $legacynote = $issuer->issue_if_eligible($legacyrefund);
        $mainnote = $issuer->issue_if_eligible($mainrefund);

        self::assertNotNull($legacynote);
        self::assertNotNull($mainnote);
        self::assertSame(1, $legacynote->sequence);
        self::assertSame(2, $mainnote->sequence);
        self::assertStringEndsWith('-000001', $legacynote->number);
        self::assertStringEndsWith('-000002', $mainnote->number);
        self::assertStringStartsWith('CFR-FR-A-', $legacynote->number);
        self::assertStringStartsWith('CFR-FR-A-', $mainnote->number);
    }

    private function insert_existing_invoice(
        string $entitykey,
        int $year,
        int $sequence,
        string $number
    ): void {
        global $DB;

        $purchaseid = $this->purchase('cmp_' . bin2hex(random_bytes(12)));
        $now = time();
        $DB->insert_record(CommerceInvoiceIssuer::TABLE_INVOICE, (object)[
            'purchaseid' => $purchaseid,
            'entitykey' => $entitykey,
            'invoicenumber' => $number,
            'invoiceyear' => $year,
            'sequence' => $sequence,
            'issuedat' => $now,
            'sellerjson' => '{}',
            'customerjson' => '{}',
            'financialjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function purchase(string $reference): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => bin2hex(random_bytes(16)),
            'reference' => $reference,
            'type' => 'digital',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => null,
            'customeremail' => 'buyer@example.test',
            'status' => 'pending',
            'currency' => 'EUR',
            'subtotalminor' => 1000,
            'discountminor' => 0,
            'totalminor' => 1000,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function order(
        int $purchaseid,
        string $entitykey,
        string $sellername
    ): CommerceOrderPresentation {
        $now = time();
        $seller = [
            'version' => 1,
            'legal_entity_key' => $entitykey,
            'registered_country' => 'FR',
            'name' => $sellername,
            'address' => 'Snapshot address',
            'legal' => 'Snapshot legal',
            'registration' => 'REG-M13',
            'tax_identifier' => '',
            'email' => 'legal@example.test',
            'phone' => '',
            'website' => '',
            'tax_notice' => '',
            'footer' => '',
            'market_country' => 'FR',
            'resolution_rule' => 'market_row',
            'resolved_at' => $now,
            'currency' => 'EUR',
            'provider' => 'stripe',
        ];

        return new CommerceOrderPresentation(
            $purchaseid,
            bin2hex(random_bytes(16)),
            'cmp_' . bin2hex(random_bytes(12)),
            'digital',
            null,
            'buyer@example.test',
            'EUR',
            1000,
            'paid',
            'paid',
            'completed',
            'stripe',
            $now,
            $now,
            [],
            [],
            [],
            [],
            null,
            ['metadata' => ['legal_entity_snapshot' => $seller]],
            [
                'userid' => null,
                'email' => 'buyer@example.test',
                'firstname' => 'Buyer',
                'lastname' => 'Snapshot',
                'fullname' => 'Buyer Snapshot',
                'country' => 'FR',
                'language' => 'fr',
                'metadata' => [],
            ]
        );
    }

    private function create_paid_purchase(
        string $entitykey,
        string $sellername,
        string $currency,
        int $totalminor
    ): array {
        global $DB;

        $now = time();
        $reference = 'cmp_' . bin2hex(random_bytes(12));
        $seller = [
            'version' => 1,
            'legal_entity_key' => $entitykey,
            'registered_country' => 'FR',
            'name' => $sellername,
            'address' => 'Snapshot address',
            'legal' => 'Snapshot legal',
            'registration' => 'REG-M13',
            'tax_identifier' => '',
            'email' => 'legal@example.test',
            'phone' => '',
            'website' => '',
            'tax_notice' => '',
            'footer' => '',
            'market_country' => 'FR',
            'resolution_rule' => 'market_row',
            'resolved_at' => $now,
            'currency' => $currency,
            'provider' => 'stripe',
        ];
        $customer = [
            'userid' => null,
            'email' => 'buyer@example.test',
            'firstname' => 'Buyer',
            'lastname' => 'Snapshot',
            'fullname' => 'Buyer Snapshot',
            'country' => 'FR',
            'language' => 'fr',
            'metadata' => [],
        ];

        $purchaseid = (int)$DB->insert_record(
            'local_subscriptions_commerce_purchase',
            (object)[
                'purchaseuuid' => bin2hex(random_bytes(16)),
                'reference' => $reference,
                'type' => 'digital',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => null,
                'customeremail' => 'buyer@example.test',
                'status' => 'paid',
                'currency' => $currency,
                'subtotalminor' => $totalminor,
                'discountminor' => 0,
                'totalminor' => $totalminor,
                'customerjson' => json_encode($customer),
                'snapshotjson' => json_encode([
                    'metadata' => ['legal_entity_snapshot' => $seller],
                ]),
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $paymentid = (int)$DB->insert_record(
            'local_subscriptions_commerce_payment',
            (object)[
                'purchaseid' => $purchaseid,
                'sequence' => 0,
                'provider' => 'stripe',
                'providerreference' => 'provider-' . bin2hex(random_bytes(4)),
                'providerorderid' => null,
                'status' => 'paid',
                'currency' => $currency,
                'amountminor' => $totalminor,
                'transactionid' => 'txn-' . bin2hex(random_bytes(4)),
                'legacyrequestid' => null,
                'paidat' => $now,
                'metadatajson' => '{}',
                'paymenturl' => null,
                'providerpayload' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        return [$purchaseid, $paymentid];
    }

    private function successful_refund(
        CommercePaymentRefundRepository $refunds,
        int $paymentid,
        string $idempotencykey,
        string $providerrefundid
    ) {
        $pending = $refunds->create_pending(
            $paymentid,
            'stripe',
            $idempotencykey,
            'EUR',
            1000,
            'Test refund',
            [],
            null
        );

        return $refunds->complete(
            $pending->get_id(),
            new CommercePaymentRefundResult(
                'stripe',
                $providerrefundid,
                CommercePaymentRefundResult::STATUS_SUCCEEDED,
                'EUR',
                1000,
                []
            )
        );
    }
}
