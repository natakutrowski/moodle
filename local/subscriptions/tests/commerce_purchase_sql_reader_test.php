<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use coding_exception;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlHydrator;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlReader;

/**
 * Tests the native Commerce SQL reader.
 *
 * @covers \local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlReader
 * @covers \local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlHydrator
 */
final class commerce_purchase_sql_reader_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    public function test_reader_finds_a_complete_purchase_by_uuid(): void {
        $fixture = $this->create_complete_purchase('uuid-read');

        $snapshot = $this->make_reader()->find_by_uuid(
            $fixture['purchaseuuid']
        );

        $this->assertNotNull($snapshot);

        $purchase = $snapshot->get_purchase()->to_record();

        $this->assertSame(
            $fixture['purchaseuuid'],
            $purchase->purchaseuuid
        );
        $this->assertSame(
            $fixture['reference'],
            $purchase->reference
        );
        $this->assertSame(2, count($snapshot->get_items()));
        $this->assertSame(2, count($snapshot->get_payments()));
        $this->assertSame(2, count($snapshot->get_fulfillments()));
    }

    public function test_reader_finds_a_complete_purchase_by_reference(): void {
        $fixture = $this->create_complete_purchase('reference-read');

        $snapshot = $this->make_reader()->find_by_reference(
            $fixture['reference']
        );

        $this->assertNotNull($snapshot);

        $purchase = $snapshot->get_purchase()->to_record();

        $this->assertSame(
            $fixture['purchaseuuid'],
            $purchase->purchaseuuid
        );
        $this->assertSame(
            $fixture['reference'],
            $purchase->reference
        );
    }

    public function test_reader_returns_null_when_native_identity_is_unknown(): void {
        $reader = $this->make_reader();

        $this->assertNull(
            $reader->find_by_uuid(md5('unknown-purchase'))
        );

        $this->assertNull(
            $reader->find_by_reference(
                'cmp_' . substr(md5('unknown-reference'), 0, 24)
            )
        );
    }

    public function test_reader_preserves_child_order(): void {
        $fixture = $this->create_complete_purchase('ordered-read');

        $snapshot = $this->make_reader()->find_by_uuid(
            $fixture['purchaseuuid']
        );

        $this->assertNotNull($snapshot);

        $items = $snapshot->get_items();
        $payments = $snapshot->get_payments();
        $fulfillments = $snapshot->get_fulfillments();

        $this->assertSame(0, $items[0]->to_record()->position);
        $this->assertSame(1, $items[1]->to_record()->position);

        $this->assertSame(0, $payments[0]->to_record()->sequence);
        $this->assertSame(1, $payments[1]->to_record()->sequence);

        $this->assertSame(
            0,
            $fulfillments[0]->to_record()->sequence
        );
        $this->assertSame(
            1,
            $fulfillments[1]->to_record()->sequence
        );
    }

    public function test_reader_finds_purchase_by_legacy_reference(): void {
        $fixture = $this->create_complete_purchase(
            'legacy-reference',
            'subscription',
            42,
            142
        );

        $snapshot = $this->make_reader()->find_by_legacy_reference(
            'subscription',
            42
        );

        $this->assertNotNull($snapshot);

        $purchase = $snapshot->get_purchase()->to_record();

        $this->assertSame(
            $fixture['purchaseuuid'],
            $purchase->purchaseuuid
        );
        $this->assertSame('subscription', $purchase->legacyfamily);
        $this->assertSame(42, $purchase->legacyid);
    }

    public function test_reader_reports_legacy_reference_existence(): void {
        $this->create_complete_purchase(
            'legacy-exists',
            'digital_purchase',
            17,
            217
        );

        $reader = $this->make_reader();

        $this->assertTrue(
            $reader->exists_for_legacy_reference(
                'digital_purchase',
                17
            )
        );

        $this->assertFalse(
            $reader->exists_for_legacy_reference(
                'digital_purchase',
                999999
            )
        );

        $this->assertFalse(
            $reader->exists_for_legacy_reference('', 17)
        );

        $this->assertFalse(
            $reader->exists_for_legacy_reference(
                'digital_purchase',
                0
            )
        );
    }

    public function test_reader_finds_purchase_by_legacy_payment_request(): void {
        $fixture = $this->create_complete_purchase(
            'legacy-payment',
            'subscription',
            72,
            372
        );

        $snapshot = $this->make_reader()
            ->find_by_legacy_payment_request(
                'subscription',
                372
            );

        $this->assertNotNull($snapshot);

        $purchase = $snapshot->get_purchase()->to_record();

        $this->assertSame(
            $fixture['purchaseuuid'],
            $purchase->purchaseuuid
        );
    }

    public function test_reader_returns_null_for_unknown_legacy_references(): void {
        $reader = $this->make_reader();

        $this->assertNull(
            $reader->find_by_legacy_reference(
                'subscription',
                999999
            )
        );

        $this->assertNull(
            $reader->find_by_legacy_payment_request(
                'subscription',
                999999
            )
        );
    }

    public function test_reader_rejects_an_empty_native_uuid(): void {
        $this->expectException(coding_exception::class);

        $this->make_reader()->find_by_uuid('');
    }

    public function test_reader_rejects_an_empty_native_reference(): void {
        $this->expectException(coding_exception::class);

        $this->make_reader()->find_by_reference('');
    }

    public function test_reader_rejects_an_empty_legacy_family(): void {
        $this->expectException(coding_exception::class);

        $this->make_reader()->find_by_legacy_reference('', 42);
    }

    public function test_reader_rejects_a_non_positive_legacy_id(): void {
        $this->expectException(coding_exception::class);

        $this->make_reader()->find_by_legacy_reference(
            'subscription',
            0
        );
    }

    public function test_reader_rejects_a_non_positive_payment_request_id(): void {
        $this->expectException(coding_exception::class);

        $this->make_reader()->find_by_legacy_payment_request(
            'subscription',
            0
        );
    }

    public function test_reader_detects_an_ambiguous_legacy_payment_request(): void {
        $this->create_complete_purchase(
            'legacy-collision-one',
            'subscription',
            501,
            950
        );

        $this->create_complete_purchase(
            'legacy-collision-two',
            'subscription',
            502,
            950
        );

        $this->expectException(coding_exception::class);

        $this->make_reader()->find_by_legacy_payment_request(
            'subscription',
            950
        );
    }

    private function make_reader(): CommercePurchaseSqlReader {
        global $DB;

        return new CommercePurchaseSqlReader(
            $DB,
            new CommercePurchaseSqlHydrator()
        );
    }

    /**
     * Create a complete native Commerce SQL fixture.
     *
     * Children are deliberately inserted in reverse order so the reader's
     * explicit SQL ordering can be tested.
     *
     * @return array{
     *     purchaseid: int,
     *     purchaseuuid: string,
     *     reference: string
     * }
     */
    private function create_complete_purchase(
        string $suffix,
        string $legacyfamily = 'subscription',
        int $legacyid = 1,
        int $legacyrequestid = 101
    ): array {
        global $DB;

        $purchaseuuid = md5('purchase-' . $suffix);
        $reference = 'cmp_' . substr(
            md5('reference-' . $suffix),
            0,
            24
        );

        $purchaseid = (int) $DB->insert_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            (object) [
                'purchaseuuid' => $purchaseuuid,
                'reference' => $reference,
                'type' => 'bundle',
                'legacyfamily' => $legacyfamily,
                'legacyid' => $legacyid,
                'userid' => null,
                'customeremail' => 'customer@example.com',
                'status' => 'completed',
                'currency' => 'EUR',
                'subtotalminor' => 14500,
                'discountminor' => 2000,
                'totalminor' => 12500,
                'customerjson' => '{"email":"customer@example.com"}',
                'snapshotjson' => '{"version":1}',
                'metadatajson' => '{"source":"phpunit"}',
                'snapshotversion' => 1,
                'timecreated' => 1721700000,
                'timemodified' => 1721800000,
            ]
        );

        $this->insert_items($purchaseid, $suffix);
        $this->insert_payments(
            $purchaseid,
            $suffix,
            $legacyrequestid
        );
        $this->insert_fulfillments($purchaseid, $suffix);

        return [
            'purchaseid' => $purchaseid,
            'purchaseuuid' => $purchaseuuid,
            'reference' => $reference,
        ];
    }

    private function insert_items(
        int $purchaseid,
        string $suffix
    ): void {
        global $DB;

        $DB->insert_record(
            CommercePersistenceSchema::TABLE_ITEM,
            (object) [
                'purchaseid' => $purchaseid,
                'position' => 1,
                'itemtype' => 'digital',
                'itemreference' => 'ebook-' . $suffix,
                'label' => 'E-book',
                'quantity' => 1,
                'currency' => 'EUR',
                'unitminor' => 2500,
                'grossminor' => 2500,
                'discountminor' => 0,
                'netminor' => 2500,
                'pricingjson' => '{}',
                'fulfillmentjson' => '{}',
                'metadatajson' => '{}',
            ]
        );

        $DB->insert_record(
            CommercePersistenceSchema::TABLE_ITEM,
            (object) [
                'purchaseid' => $purchaseid,
                'position' => 0,
                'itemtype' => 'subscription',
                'itemreference' => 'plan-' . $suffix,
                'label' => 'Course access',
                'quantity' => 1,
                'currency' => 'EUR',
                'unitminor' => 12000,
                'grossminor' => 12000,
                'discountminor' => 2000,
                'netminor' => 10000,
                'pricingjson' => '{}',
                'fulfillmentjson' => '{}',
                'metadatajson' => '{}',
            ]
        );
    }

    private function insert_payments(
        int $purchaseid,
        string $suffix,
        int $legacyrequestid
    ): void {
        global $DB;

        $DB->insert_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            (object) [
                'purchaseid' => $purchaseid,
                'sequence' => 1,
                'provider' => 'stripe',
                'providerreference' => 'cs_retry_' . $suffix,
                'status' => 'completed',
                'currency' => 'EUR',
                'amountminor' => 12500,
                'transactionid' => 'pi_' . $suffix,
                'legacyrequestid' => $legacyrequestid,
                'paidat' => 1721800000,
                'metadatajson' => '{}',
            ]
        );

        $DB->insert_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            (object) [
                'purchaseid' => $purchaseid,
                'sequence' => 0,
                'provider' => 'stripe',
                'providerreference' => 'cs_initial_' . $suffix,
                'status' => 'failed',
                'currency' => 'EUR',
                'amountminor' => 12500,
                'transactionid' => null,
                'legacyrequestid' => null,
                'paidat' => null,
                'metadatajson' => '{}',
            ]
        );
    }

    private function insert_fulfillments(
        int $purchaseid,
        string $suffix
    ): void {
        global $DB;

        $DB->insert_record(
            CommercePersistenceSchema::TABLE_FULFILLMENT,
            (object) [
                'purchaseid' => $purchaseid,
                'sequence' => 1,
                'reference' => 'digital-' . $suffix,
                'fulfillmentkey' => 'digital_access',
                'idempotencykey' => 'digital:' . $suffix,
                'status' => 'pending',
                'metadatajson' => '{}',
            ]
        );

        $DB->insert_record(
            CommercePersistenceSchema::TABLE_FULFILLMENT,
            (object) [
                'purchaseid' => $purchaseid,
                'sequence' => 0,
                'reference' => 'course-' . $suffix,
                'fulfillmentkey' => 'course_access',
                'idempotencykey' => 'course:' . $suffix,
                'status' => 'pending',
                'metadatajson' => '{}',
            ]
        );
    }
}