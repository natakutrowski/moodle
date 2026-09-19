<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\invoice\CommerceInvoiceIssuer;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;

/** M1.2 invoice numbering must recover from missing or stale allocation cursors. */
final class commerce_797m12_invoice_sequence_reconciliation_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_missing_counter_is_rebuilt_from_existing_invoice_ledger(): void {
        global $DB;

        $year = (int)userdate(time(), '%Y');
        $this->insert_existing_invoice('fr_main', $year, 1, 'CFR-FR-' . $year . '-000001');
        $this->assertSame(0, $DB->count_records(CommerceInvoiceIssuer::TABLE_SEQUENCE));

        $purchaseid = $this->purchase('cmp_' . bin2hex(random_bytes(12)));
        $invoice = (new CommerceInvoiceIssuer($DB))->get_or_issue(
            $this->order($purchaseid, 'fr_main', 'CampusFR France')
        );

        $this->assertSame(2, $invoice->sequence);
        $this->assertSame('CFR-FR-' . $year . '-000002', $invoice->number);

        $counter = $DB->get_record(
            CommerceInvoiceIssuer::TABLE_SEQUENCE,
            ['entitykey' => 'fr_main', 'invoiceyear' => $year],
            '*',
            MUST_EXIST
        );
        $this->assertSame(2, (int)$counter->lastsequence);
    }

    public function test_stale_counter_is_advanced_to_maximum_issued_sequence(): void {
        global $DB;

        $year = (int)userdate(time(), '%Y');
        $this->insert_existing_invoice('fr_main', $year, 3, 'CFR-FR-' . $year . '-000003');
        $DB->insert_record(CommerceInvoiceIssuer::TABLE_SEQUENCE, (object)[
            'entitykey' => 'fr_main',
            'invoiceyear' => $year,
            'lastsequence' => 1,
            'timemodified' => time(),
        ]);

        $purchaseid = $this->purchase('cmp_' . bin2hex(random_bytes(12)));
        $invoice = (new CommerceInvoiceIssuer($DB))->get_or_issue(
            $this->order($purchaseid, 'fr_main', 'CampusFR France')
        );

        $this->assertSame(4, $invoice->sequence);
        $this->assertSame('CFR-FR-' . $year . '-000004', $invoice->number);

        $counter = $DB->get_record(
            CommerceInvoiceIssuer::TABLE_SEQUENCE,
            ['entitykey' => 'fr_main', 'invoiceyear' => $year],
            '*',
            MUST_EXIST
        );
        $this->assertSame(4, (int)$counter->lastsequence);
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
            'registration' => 'REG-M12',
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
}
