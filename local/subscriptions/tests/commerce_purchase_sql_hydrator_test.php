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
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlHydrator;

/**
 * Tests the SQL persistence snapshot hydrator.
 *
 * @covers \local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlHydrator
 */
final class commerce_purchase_sql_hydrator_test extends advanced_testcase {
    public function test_hydrator_reconstructs_a_complete_snapshot(): void {
        $hydrator = new CommercePurchaseSqlHydrator();

        $purchase = $this->make_purchase_record();

        $items = [
            (object) [
                'position' => 0,
                'itemtype' => 'subscription',
                'itemreference' => 'plan-a1-full',
                'label' => 'CampusFR A1 Full',
                'quantity' => 1,
                'currency' => 'EUR',
                'unitminor' => 12000,
                'grossminor' => 12000,
                'discountminor' => 2000,
                'netminor' => 10000,
                'pricingjson' => '{"source":"catalogue"}',
                'fulfillmentjson' => '{"courseids":[17]}',
                'metadatajson' => '{}',
            ],
            (object) [
                'position' => 1,
                'itemtype' => 'digital',
                'itemreference' => 'ebook-a1',
                'label' => 'E-book A1',
                'quantity' => 1,
                'currency' => 'EUR',
                'unitminor' => 2500,
                'grossminor' => 2500,
                'discountminor' => 0,
                'netminor' => 2500,
                'pricingjson' => '{}',
                'fulfillmentjson' => '{"digitalproductid":12}',
                'metadatajson' => '{}',
            ],
        ];

        $payments = [
            (object) [
                'sequence' => 0,
                'provider' => 'stripe',
                'providerreference' => 'cs_test_123',
                'status' => 'completed',
                'currency' => 'EUR',
                'amountminor' => 12500,
                'transactionid' => 'pi_test_123',
                'legacyrequestid' => 42,
                'paidat' => 1721800000,
                'metadatajson' => '{"mode":"test"}',
            ],
        ];

        $fulfillments = [
            (object) [
                'sequence' => 0,
                'reference' => 'course-access-17',
                'fulfillmentkey' => 'course_access',
                'idempotencykey' => 'cmp:test:course:17',
                'status' => 'pending',
                'metadatajson' => '{"courseid":17}',
            ],
            (object) [
                'sequence' => 1,
                'reference' => 'digital-access-12',
                'fulfillmentkey' => 'digital_access',
                'idempotencykey' => 'cmp:test:digital:12',
                'status' => 'pending',
                'metadatajson' => '{"digitalproductid":12}',
            ],
        ];

        $snapshot = $hydrator->hydrate(
            $purchase,
            $items,
            $payments,
            $fulfillments
        );

        $purchaserecord = $snapshot->get_purchase()->to_record();

        $this->assertSame(
            $purchase->purchaseuuid,
            $purchaserecord->purchaseuuid
        );
        $this->assertSame(
            $purchase->reference,
            $purchaserecord->reference
        );
        $this->assertSame(2, count($snapshot->get_items()));
        $this->assertSame(1, count($snapshot->get_payments()));
        $this->assertSame(2, count($snapshot->get_fulfillments()));

        $itemrecords = $snapshot->get_items();
        $paymentrecords = $snapshot->get_payments();
        $fulfillmentrecords = $snapshot->get_fulfillments();

        $this->assertSame(
            0,
            $itemrecords[0]->to_record()->position
        );
        $this->assertSame(
            'plan-a1-full',
            $itemrecords[0]->to_record()->itemreference
        );

        $this->assertSame(
            'stripe',
            $paymentrecords[0]->to_record()->provider
        );
        $this->assertSame(
            'pi_test_123',
            $paymentrecords[0]->to_record()->transactionid
        );

        $this->assertSame(
            'course_access',
            $fulfillmentrecords[0]->to_record()->fulfillmentkey
        );
        $this->assertSame(
            'cmp:test:course:17',
            $fulfillmentrecords[0]->to_record()->idempotencykey
        );
    }

    public function test_hydrator_normalises_nullable_database_values(): void {
        $hydrator = new CommercePurchaseSqlHydrator();

        $purchase = $this->make_purchase_record();

        $purchase->legacyfamily = '';
        $purchase->legacyid = 0;
        $purchase->userid = 0;
        $purchase->customeremail = '';
        $purchase->timecreated = 0;
        $purchase->timemodified = 0;

        $payments = [
            (object) [
                'sequence' => 0,
                'provider' => '',
                'providerreference' => '',
                'status' => 'pending',
                'currency' => 'EUR',
                'amountminor' => 12500,
                'transactionid' => '',
                'legacyrequestid' => 0,
                'paidat' => 0,
                'metadatajson' => '{}',
            ],
        ];

        $items = [
            (object) [
                'position' => 0,
                'itemtype' => 'subscription',
                'itemreference' => 'plan-nullable-test',
                'label' => 'Nullable test item',
                'quantity' => 1,
                'currency' => 'EUR',
                'unitminor' => 12500,
                'grossminor' => 12500,
                'discountminor' => 0,
                'netminor' => 12500,
                'pricingjson' => '{}',
                'fulfillmentjson' => '{}',
                'metadatajson' => '{}',
            ],
        ];

        $snapshot = $hydrator->hydrate(
            $purchase,
            $items,
            $payments,
            []
        );        

        $purchaserecord = $snapshot->get_purchase()->to_record();
        $paymentrecord = $snapshot->get_payments()[0]->to_record();

        $this->assertNull($purchaserecord->legacyfamily);
        $this->assertNull($purchaserecord->legacyid);
        $this->assertNull($purchaserecord->userid);
        $this->assertNull($purchaserecord->customeremail);
        $this->assertNull($purchaserecord->timecreated);
        $this->assertNull($purchaserecord->timemodified);

        $this->assertNull($paymentrecord->provider);
        $this->assertNull($paymentrecord->providerreference);
        $this->assertNull($paymentrecord->transactionid);
        $this->assertNull($paymentrecord->legacyrequestid);
        $this->assertNull($paymentrecord->paidat);
    }

    public function test_hydrator_rejects_an_invalid_persisted_aggregate(): void {
        $hydrator = new CommercePurchaseSqlHydrator();

        $purchase = $this->make_purchase_record();

        $purchase->subtotalminor = 10000;
        $purchase->discountminor = 1000;
        $purchase->totalminor = 10000;

        $this->expectException(coding_exception::class);

        $hydrator->hydrate(
            $purchase,
            [],
            [],
            []
        );
    }

    private function make_purchase_record(): \stdClass {
        return (object) [
            'purchaseuuid' => md5('hydrator-purchase'),
            'reference' => 'cmp_' . substr(md5('hydrator-reference'), 0, 24),
            'type' => 'bundle',
            'legacyfamily' => 'subscription',
            'legacyid' => 84,
            'userid' => 96,
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
        ];
    }
}