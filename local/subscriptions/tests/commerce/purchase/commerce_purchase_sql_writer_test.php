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

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use dml_write_exception;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\record\CommerceFulfillmentRecord;
use local_subscriptions\commerce\persistence\record\CommercePaymentRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlWriter;

/**
 * Tests the native Commerce SQL writer.
 *
 * @covers \local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlWriter
 */
final class commerce_purchase_sql_writer_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    public function test_writer_persists_the_complete_snapshot(): void {
        global $DB;

        $writer = new CommercePurchaseSqlWriter($DB);

        $snapshot = $this->make_complete_snapshot(
            'complete-persistence'
        );

        $purchaseid = $writer->write($snapshot);

        $this->assertGreaterThan(0, $purchaseid);

        $purchase = $DB->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $purchaseid],
            '*',
            MUST_EXIST
        );

        $this->assertSame(
            md5('purchase-complete-persistence'),
            $purchase->purchaseuuid
        );

        $this->assertSame(
            'completed',
            $purchase->status
        );

        $this->assertSame(
            14500,
            (int) $purchase->subtotalminor
        );

        $this->assertSame(
            2000,
            (int) $purchase->discountminor
        );

        $this->assertSame(
            12500,
            (int) $purchase->totalminor
        );

        $this->assertSame(
            2,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_ITEM,
                ['purchaseid' => $purchaseid]
            )
        );

        $this->assertSame(
            2,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_PAYMENT,
                ['purchaseid' => $purchaseid]
            )
        );

        $this->assertSame(
            2,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_FULFILLMENT,
                ['purchaseid' => $purchaseid]
            )
        );
    }

    public function test_writer_updates_an_existing_purchase_by_uuid(): void {
        global $DB;

        $writer = new CommercePurchaseSqlWriter($DB);

        $firstsnapshot = $this->make_single_item_snapshot(
            'update-existing',
            'pending',
            1721700000,
            'plan-a1-initial'
        );

        $firstid = $writer->write($firstsnapshot);

        $secondsnapshot = $this->make_single_item_snapshot(
            'update-existing',
            'completed',
            1721900000,
            'plan-a1-updated'
        );

        $secondid = $writer->write($secondsnapshot);

        $this->assertSame($firstid, $secondid);

        $this->assertSame(
            1,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_PURCHASE
            )
        );

        $purchase = $DB->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $firstid],
            '*',
            MUST_EXIST
        );

        $this->assertSame(
            'completed',
            $purchase->status
        );

        $this->assertSame(
            1721700000,
            (int) $purchase->timecreated
        );

        $this->assertSame(
            1721900000,
            (int) $purchase->timemodified
        );

        $items = $DB->get_records(
            CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => $firstid],
            'position ASC'
        );

        $this->assertCount(1, $items);

        $item = reset($items);

        $this->assertSame(
            'plan-a1-updated',
            $item->itemreference
        );
    }

    public function test_repeated_save_with_same_uuid_is_idempotent(): void {
        global $DB;

        $writer = new CommercePurchaseSqlWriter($DB);

        $snapshot = $this->make_complete_snapshot(
            'idempotent-save'
        );

        $firstid = $writer->write($snapshot);
        $secondid = $writer->write($snapshot);

        $this->assertSame($firstid, $secondid);

        $this->assertSame(
            1,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_PURCHASE
            )
        );

        $this->assertSame(
            2,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_ITEM,
                ['purchaseid' => $firstid]
            )
        );

        $this->assertSame(
            2,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_PAYMENT,
                ['purchaseid' => $firstid]
            )
        );

        $this->assertSame(
            2,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_FULFILLMENT,
                ['purchaseid' => $firstid]
            )
        );
    }

    public function test_repeated_save_replaces_stale_children(): void {
        global $DB;

        $writer = new CommercePurchaseSqlWriter($DB);

        $firstsnapshot = $this->make_complete_snapshot(
            'replace-children'
        );

        $purchaseid = $writer->write($firstsnapshot);

        $secondsnapshot = $this->make_single_item_snapshot(
            'replace-children',
            'completed',
            1721900000,
            'plan-a1-replacement'
        );

        $writer->write($secondsnapshot);

        $items = $DB->get_records(
            CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => $purchaseid],
            'position ASC'
        );

        $this->assertCount(1, $items);

        $item = reset($items);

        $this->assertSame(
            0,
            (int) $item->position
        );

        $this->assertSame(
            'plan-a1-replacement',
            $item->itemreference
        );

        $this->assertSame(
            0,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_PAYMENT,
                ['purchaseid' => $purchaseid]
            )
        );

        $this->assertSame(
            0,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_FULFILLMENT,
                ['purchaseid' => $purchaseid]
            )
        );
    }

    public function test_child_constraint_failure_rolls_back_complete_insert(): void {
        global $DB;

        $writer = new CommercePurchaseSqlWriter($DB);

        $existingsnapshot =
            $this->make_snapshot_with_fulfillment(
                'existing-purchase',
                'global-idempotency-key'
            );

        $existingid = $writer->write(
            $existingsnapshot
        );

        $failingsnapshot =
            $this->make_snapshot_with_fulfillment(
                'failing-purchase',
                'global-idempotency-key'
            );

        try {
            $writer->write($failingsnapshot);

            $this->fail(
                'A duplicate fulfillment idempotency key '
                    . 'was unexpectedly accepted.'
            );
        } catch (dml_write_exception) {
            // Expected database constraint failure.
        }

        $this->assertSame(
            1,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_PURCHASE
            )
        );

        $this->assertTrue(
            $DB->record_exists(
                CommercePersistenceSchema::TABLE_PURCHASE,
                ['id' => $existingid]
            )
        );

        $this->assertFalse(
            $DB->record_exists(
                CommercePersistenceSchema::TABLE_PURCHASE,
                [
                    'purchaseuuid' =>
                        md5('purchase-failing-purchase'),
                ]
            )
        );

        $this->assertSame(
            1,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_ITEM
            )
        );

        $this->assertSame(
            1,
            $DB->count_records(
                CommercePersistenceSchema::TABLE_FULFILLMENT
            )
        );
    }

    public function test_failed_update_restores_previous_purchase_and_children(): void {
        global $DB;

        $writer = new CommercePurchaseSqlWriter($DB);

        $protectedsnapshot =
            $this->make_snapshot_with_fulfillment(
                'protected-purchase',
                'protected-idempotency-key',
                'pending'
            );

        $protectedid = $writer->write(
            $protectedsnapshot
        );

        $othersnapshot =
            $this->make_snapshot_with_fulfillment(
                'other-purchase',
                'other-idempotency-key',
                'pending'
            );

        $writer->write($othersnapshot);

        $failingupdate =
            $this->make_snapshot_with_fulfillment(
                'protected-purchase',
                'other-idempotency-key',
                'completed'
            );

        try {
            $writer->write($failingupdate);

            $this->fail(
                'The invalid Commerce purchase update '
                    . 'was unexpectedly accepted.'
            );
        } catch (dml_write_exception) {
            // Expected database constraint failure.
        }

        $purchase = $DB->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $protectedid],
            '*',
            MUST_EXIST
        );

        $this->assertSame(
            'pending',
            $purchase->status
        );

        $items = $DB->get_records(
            CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => $protectedid]
        );

        $this->assertCount(1, $items);

        $fulfillments = $DB->get_records(
            CommercePersistenceSchema::TABLE_FULFILLMENT,
            ['purchaseid' => $protectedid]
        );

        $this->assertCount(1, $fulfillments);

        $fulfillment = reset($fulfillments);

        $this->assertSame(
            'protected-idempotency-key',
            $fulfillment->idempotencykey
        );
    }

    private function make_complete_snapshot(
        string $suffix
    ): CommercePurchasePersistenceSnapshot {
        $purchaseuuid = md5('purchase-' . $suffix);

        $purchase = $this->make_purchase_record(
            $purchaseuuid,
            $suffix,
            'completed',
            14500,
            2000,
            12500,
            1721700000,
            1721800000
        );

        $items = [
            new CommercePurchaseItemRecord(
                $purchaseuuid,
                0,
                'subscription',
                'plan-a1-full',
                'CampusFR A1 Full',
                1,
                'EUR',
                12000,
                12000,
                2000,
                10000,
                '{"source":"catalogue"}',
                '{"courseids":[17]}',
                '{}'
            ),
            new CommercePurchaseItemRecord(
                $purchaseuuid,
                1,
                'digital',
                'ebook-a1',
                'E-book A1',
                1,
                'EUR',
                2500,
                2500,
                0,
                2500,
                '{}',
                '{"digitalproductid":12}',
                '{}'
            ),
        ];

        $payments = [
            new CommercePaymentRecord(
                $purchaseuuid,
                0,
                'stripe',
                'cs_initial_' . $suffix,
                'failed',
                'EUR',
                12500,
                null,
                null,
                null,
                '{}'
            ),
            new CommercePaymentRecord(
                $purchaseuuid,
                1,
                'stripe',
                'cs_completed_' . $suffix,
                'completed',
                'EUR',
                12500,
                'pi_' . $suffix,
                $this->make_legacy_payment_id($suffix),
                1721800000,
                '{"mode":"test"}'
            ),
        ];

        $fulfillments = [
            new CommerceFulfillmentRecord(
                $purchaseuuid,
                0,
                'course-access-' . $suffix,
                'course_access',
                'course-access:' . $suffix,
                'pending',
                '{"courseid":17}'
            ),
            new CommerceFulfillmentRecord(
                $purchaseuuid,
                1,
                'digital-access-' . $suffix,
                'digital_access',
                'digital-access:' . $suffix,
                'pending',
                '{"digitalproductid":12}'
            ),
        ];

        return new CommercePurchasePersistenceSnapshot(
            $purchase,
            $items,
            $payments,
            $fulfillments
        );
    }

    private function make_single_item_snapshot(
        string $suffix,
        string $status,
        int $timemodified,
        string $itemreference
    ): CommercePurchasePersistenceSnapshot {
        $purchaseuuid = md5('purchase-' . $suffix);

        $purchase = $this->make_purchase_record(
            $purchaseuuid,
            $suffix,
            $status,
            12000,
            0,
            12000,
            1721700000,
            $timemodified
        );

        $item = new CommercePurchaseItemRecord(
            $purchaseuuid,
            0,
            'subscription',
            $itemreference,
            'CampusFR A1',
            1,
            'EUR',
            12000,
            12000,
            0,
            12000,
            '{}',
            '{"courseids":[17]}',
            '{}'
        );

        return new CommercePurchasePersistenceSnapshot(
            $purchase,
            [$item],
            [],
            []
        );
    }

    private function make_snapshot_with_fulfillment(
        string $suffix,
        string $idempotencykey,
        string $status = 'pending'
    ): CommercePurchasePersistenceSnapshot {
        $purchaseuuid = md5('purchase-' . $suffix);

        $purchase = $this->make_purchase_record(
            $purchaseuuid,
            $suffix,
            $status,
            12000,
            0,
            12000,
            1721700000,
            1721800000
        );

        $item = new CommercePurchaseItemRecord(
            $purchaseuuid,
            0,
            'subscription',
            'plan-' . $suffix,
            'CampusFR A1',
            1,
            'EUR',
            12000,
            12000,
            0,
            12000,
            '{}',
            '{"courseids":[17]}',
            '{}'
        );

        $fulfillment = new CommerceFulfillmentRecord(
            $purchaseuuid,
            0,
            'course-access-' . $suffix,
            'course_access',
            $idempotencykey,
            'pending',
            '{"courseid":17}'
        );

        return new CommercePurchasePersistenceSnapshot(
            $purchase,
            [$item],
            [],
            [$fulfillment]
        );
    }

    private function make_purchase_record(
        string $purchaseuuid,
        string $suffix,
        string $status,
        int $subtotalminor,
        int $discountminor,
        int $totalminor,
        int $timecreated,
        int $timemodified
    ): CommercePurchaseRecord {
        return new CommercePurchaseRecord(
            $purchaseuuid,
            'cmp_' . substr(
                md5('reference-' . $suffix),
                0,
                24
            ),
            'subscription',
            'subscription',
            $this->make_legacy_purchase_id($suffix),
            null,
            'customer@example.com',
            $status,
            'EUR',
            $subtotalminor,
            $discountminor,
            $totalminor,
            '{"email":"customer@example.com"}',
            '{"version":1}',
            '{"source":"phpunit"}',
            1,
            $timecreated,
            $timemodified
        );
    }

    private function make_legacy_purchase_id(
        string $suffix
    ): int {
        return ((int) sprintf(
            '%u',
            crc32('purchase-' . $suffix)
        ) % 1000000) + 1;
    }

    private function make_legacy_payment_id(
        string $suffix
    ): int {
        return ((int) sprintf(
            '%u',
            crc32('payment-' . $suffix)
        ) % 1000000) + 1;
    }
}