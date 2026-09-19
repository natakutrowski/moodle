<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\creditnote\CommerceCreditNoteIssuer;
use local_subscriptions\commerce\order\invoice\CommerceInvoiceIssuer;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;

final class commerce_796i72_i73_credit_note_ledger_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_successful_partial_refunds_get_distinct_credit_notes(): void {
        global $DB;

        [$purchaseid, $paymentid] = $this->create_paid_purchase(
            'fr_main',
            'FR',
            'CampusFR France',
            'EUR',
            10000
        );

        $refunds = new CommercePaymentRefundRepository($DB);
        $first = $this->successful_refund(
            $refunds,
            $paymentid,
            'stripe',
            'EUR',
            2500,
            'refund-partial-1',
            're_1'
        );
        $second = $this->successful_refund(
            $refunds,
            $paymentid,
            'stripe',
            'EUR',
            1500,
            'refund-partial-2',
            're_2'
        );

        $issuer = new CommerceCreditNoteIssuer($DB);
        $note1 = $issuer->issue_if_eligible($first);
        $note2 = $issuer->issue_if_eligible($second);

        self::assertNotNull($note1);
        self::assertNotNull($note2);
        self::assertSame($purchaseid, $note1->purchaseid);
        self::assertSame(2500, $note1->financial['refund_minor']);
        self::assertSame(1500, $note2->financial['refund_minor']);
        self::assertSame(1, $note1->sequence);
        self::assertSame(2, $note2->sequence);
        self::assertMatchesRegularExpression(
            '/^CFR-FR-A-\d{4}-000001$/',
            $note1->number
        );
        self::assertMatchesRegularExpression(
            '/^CFR-FR-A-\d{4}-000002$/',
            $note2->number
        );
        self::assertSame(
            2,
            $DB->count_records(CommerceCreditNoteIssuer::TABLE_CREDIT_NOTE)
        );
    }

    public function test_credit_note_is_idempotent_and_inherits_original_invoice_snapshots(): void {
        global $DB;

        [$purchaseid, $paymentid] = $this->create_paid_purchase(
            'ru_main',
            'RU',
            'Historical RU Seller',
            'RUB',
            49000
        );

        $refunds = new CommercePaymentRefundRepository($DB);
        $refund = $this->successful_refund(
            $refunds,
            $paymentid,
            'alfa',
            'RUB',
            49000,
            'refund-total',
            'alfa-refund-1'
        );

        $issuer = new CommerceCreditNoteIssuer($DB);
        $first = $issuer->issue_if_eligible($refund);

        self::assertNotNull($first);
        self::assertSame('Historical RU Seller', $first->seller['name']);
        self::assertSame('Buyer Snapshot', $first->customer['fullname']);
        self::assertSame('not_modelled_v1', $first->financial['tax_status']);
        self::assertStringStartsWith('CFR-RU-A-', $first->number);

        // Current config changes must not rewrite the historical document.
        set_config(
            'legal_entity_ru_name',
            'Changed Current RU Seller',
            'local_subscriptions'
        );

        $again = $issuer->issue_if_eligible($refund);
        self::assertNotNull($again);
        self::assertSame($first->id, $again->id);
        self::assertSame($first->number, $again->number);
        self::assertSame('Historical RU Seller', $again->seller['name']);

        $invoice = $DB->get_record(
            CommerceInvoiceIssuer::TABLE_INVOICE,
            ['purchaseid' => $purchaseid],
            '*',
            MUST_EXIST
        );
        self::assertSame((int)$invoice->id, $again->invoiceid);
        self::assertSame(
            (string)$invoice->invoicenumber,
            $again->metadata['invoice_number']
        );
    }

    public function test_credit_note_issues_original_invoice_first_when_needed(): void {
        global $DB;

        [$purchaseid, $paymentid] = $this->create_paid_purchase(
            'fr_main',
            'FR',
            'CampusFR France',
            'EUR',
            28000
        );
        self::assertFalse(
            $DB->record_exists(
                CommerceInvoiceIssuer::TABLE_INVOICE,
                ['purchaseid' => $purchaseid]
            )
        );

        $refunds = new CommercePaymentRefundRepository($DB);
        $refund = $this->successful_refund(
            $refunds,
            $paymentid,
            'stripe',
            'EUR',
            5000,
            'refund-before-download',
            're_invoice_first'
        );

        $note = (new CommerceCreditNoteIssuer($DB))
            ->issue_if_eligible($refund);

        self::assertNotNull($note);
        self::assertTrue(
            $DB->record_exists(
                CommerceInvoiceIssuer::TABLE_INVOICE,
                ['purchaseid' => $purchaseid]
            )
        );
        self::assertGreaterThan(0, $note->invoiceid);
    }

    public function test_pending_failed_and_mutable_aggregate_refunds_do_not_consume_numbers(): void {
        global $DB;

        [, $paymentid] = $this->create_paid_purchase(
            'fr_main',
            'FR',
            'CampusFR France',
            'EUR',
            10000
        );
        $refunds = new CommercePaymentRefundRepository($DB);
        $issuer = new CommerceCreditNoteIssuer($DB);

        $pending = $refunds->create_pending(
            $paymentid,
            'stripe',
            'pending-refund',
            'EUR',
            1000,
            null,
            [],
            null
        );
        self::assertNull($issuer->issue_if_eligible($pending));

        $aggregate = $refunds->create_pending(
            $paymentid,
            'alfa',
            'aggregate-refund',
            'EUR',
            2000,
            null,
            [],
            null
        );
        $aggregate = $refunds->complete(
            $aggregate->get_id(),
            new CommercePaymentRefundResult(
                'alfa',
                'aggregate-provider-ref',
                CommercePaymentRefundResult::STATUS_SUCCEEDED,
                'EUR',
                2000,
                ['aggregate_total' => true]
            )
        );
        self::assertNull($issuer->issue_if_eligible($aggregate));

        self::assertSame(
            0,
            $DB->count_records(CommerceCreditNoteIssuer::TABLE_CREDIT_NOTE)
        );
        self::assertSame(
            0,
            $DB->count_records(CommerceCreditNoteIssuer::TABLE_SEQUENCE)
        );
    }

    public function test_fr_and_ru_credit_note_sequences_are_independent(): void {
        global $DB;

        [, $frpayment] = $this->create_paid_purchase(
            'fr_main',
            'FR',
            'FR Seller',
            'EUR',
            10000
        );
        [, $rupayment] = $this->create_paid_purchase(
            'ru_main',
            'RU',
            'RU Seller',
            'RUB',
            10000
        );

        $refunds = new CommercePaymentRefundRepository($DB);
        $frrefund = $this->successful_refund(
            $refunds,
            $frpayment,
            'stripe',
            'EUR',
            1000,
            'fr-refund',
            'fr-provider-ref'
        );
        $rurefund = $this->successful_refund(
            $refunds,
            $rupayment,
            'alfa',
            'RUB',
            1000,
            'ru-refund',
            'ru-provider-ref'
        );

        $issuer = new CommerceCreditNoteIssuer($DB);
        $frnote = $issuer->issue_if_eligible($frrefund);
        $runote = $issuer->issue_if_eligible($rurefund);

        self::assertNotNull($frnote);
        self::assertNotNull($runote);
        self::assertSame(1, $frnote->sequence);
        self::assertSame(1, $runote->sequence);
        self::assertStringStartsWith('CFR-FR-A-', $frnote->number);
        self::assertStringStartsWith('CFR-RU-A-', $runote->number);
    }

    public function test_i72_i73_schema_and_version_are_present(): void {
        global $CFG;

        $installxml = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/db/install.xml'
        );
        $upgrade = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/db/upgrade.php'
        );
        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );

        self::assertStringContainsString(
            'local_subs_commerce_credit_note',
            $installxml
        );
        self::assertStringContainsString(
            'local_subs_commerce_cn_seq',
            $installxml
        );
        self::assertStringContainsString(
            '2026090801',
            $upgrade
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

    private function create_paid_purchase(
        string $entitykey,
        string $registeredcountry,
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
            'registered_country' => $registeredcountry,
            'name' => $sellername,
            'address' => 'Snapshot address',
            'legal' => 'Snapshot legal',
            'registration' => 'REG-1',
            'tax_identifier' => '',
            'email' => 'legal@example.test',
            'phone' => '',
            'website' => '',
            'tax_notice' => '',
            'footer' => '',
            'market_country' => $registeredcountry,
            'resolution_rule' =>
                $registeredcountry === 'RU'
                    ? 'market_ru_by'
                    : 'market_row',
            'resolved_at' => $now,
            'currency' => $currency,
            'provider' =>
                $registeredcountry === 'RU'
                    ? 'alfa'
                    : 'stripe',
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
                    'metadata' => [
                        'legal_entity_snapshot' => $seller,
                    ],
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
                'provider' =>
                    $registeredcountry === 'RU'
                        ? 'alfa'
                        : 'stripe',
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
        string $provider,
        string $currency,
        int $amountminor,
        string $idempotencykey,
        string $providerrefundid
    ) {
        $pending = $refunds->create_pending(
            $paymentid,
            $provider,
            $idempotencykey,
            $currency,
            $amountminor,
            'Test refund',
            [],
            null
        );

        return $refunds->complete(
            $pending->get_id(),
            new CommercePaymentRefundResult(
                $provider,
                $providerrefundid,
                CommercePaymentRefundResult::STATUS_SUCCEEDED,
                $currency,
                $amountminor,
                []
            )
        );
    }
}
