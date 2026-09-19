<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\invoice\CommerceInvoiceIssuer;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;

/** I6 invoice ledger, numbering and immutable seller/customer snapshots. */
final class commerce_796i6_invoice_ledger_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_invoice_is_issued_once_with_entity_scoped_sequence(): void {
        global $DB;
        $first = $this->order($this->purchase('cmp_' . bin2hex(random_bytes(12))), 1, 'fr_main', 'CampusFR France');
        $second = $this->order($this->purchase('cmp_' . bin2hex(random_bytes(12))), 2, 'fr_main', 'CampusFR France');

        $issuer = new CommerceInvoiceIssuer($DB);
        $invoice1 = $issuer->get_or_issue($first);
        $same = $issuer->get_or_issue($first);
        $invoice2 = $issuer->get_or_issue($second);

        $this->assertSame($invoice1->id, $same->id);
        $this->assertSame(1, $invoice1->sequence);
        $this->assertSame(2, $invoice2->sequence);
        $this->assertMatchesRegularExpression('/^CFR-FR-\d{4}-000001$/', $invoice1->number);
        $this->assertMatchesRegularExpression('/^CFR-FR-\d{4}-000002$/', $invoice2->number);
        $this->assertSame(2, $DB->count_records(CommerceInvoiceIssuer::TABLE_INVOICE));
    }

    public function test_fr_and_ru_have_independent_numbering_sequences(): void {
        global $DB;
        $fr = $this->order($this->purchase('cmp_' . bin2hex(random_bytes(12))), 1, 'fr_main', 'FR Seller', 'FR');
        $ru = $this->order($this->purchase('cmp_' . bin2hex(random_bytes(12))), 2, 'ru_main', 'RU Seller', 'RU');

        $issuer = new CommerceInvoiceIssuer($DB);
        $frinvoice = $issuer->get_or_issue($fr);
        $ruinvoice = $issuer->get_or_issue($ru);

        $this->assertSame(1, $frinvoice->sequence);
        $this->assertSame(1, $ruinvoice->sequence);
        $this->assertStringContainsString('CFR-FR-', $frinvoice->number);
        $this->assertStringContainsString('CFR-RU-', $ruinvoice->number);
    }

    public function test_issued_invoice_keeps_historical_seller_and_customer(): void {
        global $DB;
        $purchaseid = $this->purchase('cmp_' . bin2hex(random_bytes(12)));
        $order = $this->order($purchaseid, 1, 'fr_main', 'Historical Seller');
        $issuer = new CommerceInvoiceIssuer($DB);

        $invoice = $issuer->get_or_issue($order);
        set_config('legal_entity_fr_name', 'Changed Seller', 'local_subscriptions');

        $again = $issuer->get_or_issue($order);
        $this->assertSame('Historical Seller', $again->seller['name']);
        $this->assertSame('Buyer Snapshot', $again->customer['fullname']);
        $this->assertSame($invoice->number, $again->number);
    }

    public function test_unpaid_purchase_cannot_receive_invoice_number(): void {
        global $DB;
        $order = $this->order($this->purchase('cmp_' . bin2hex(random_bytes(12))), 1, 'fr_main', 'Seller', 'FR', 'pending');
        $this->expectException(coding_exception::class);
        (new CommerceInvoiceIssuer($DB))->get_or_issue($order);
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
        int $ordinal,
        string $entitykey,
        string $sellername,
        string $country = 'FR',
        string $paymentstatus = 'paid'
    ): CommerceOrderPresentation {
        $now = time();
        $seller = [
            'version' => 1,
            'legal_entity_key' => $entitykey,
            'registered_country' => $country,
            'name' => $sellername,
            'address' => 'Snapshot address',
            'legal' => 'Snapshot legal',
            'registration' => 'REG-' . $ordinal,
            'tax_identifier' => '',
            'email' => 'legal@example.test',
            'phone' => '',
            'website' => '',
            'tax_notice' => '',
            'footer' => '',
            'market_country' => $country,
            'resolution_rule' => $country === 'RU' ? 'market_ru_by' : 'market_row',
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
            $paymentstatus,
            'completed',
            'stripe',
            $paymentstatus === 'paid' ? $now : null,
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
