<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

/**
 * Tests the complete native Commerce SQL persistence repository.
 *
 * @covers \local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository
 * @covers \local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory
 */
final class commerce_purchase_sql_repository_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    public function test_repository_saves_and_reads_a_snapshot(): void {
        $repository = CommercePurchaseSqlRepositoryFactory::create();
        $snapshot = $this->make_snapshot('repository-roundtrip');

        $purchaseid = $repository->save($snapshot);

        $this->assertGreaterThan(0, $purchaseid);

        $loaded = $repository->find_by_uuid(
            $snapshot->get_purchase()->get_purchase_uuid()
        );

        $this->assertNotNull($loaded);

        $this->assertEquals(
            $snapshot->get_purchase()->to_record(),
            $loaded->get_purchase()->to_record()
        );

        $this->assertEquals(
            $snapshot->get_items()[0]->to_record(),
            $loaded->get_items()[0]->to_record()
        );
    }

    public function test_repository_can_find_saved_purchase_by_reference(): void {
        $repository = CommercePurchaseSqlRepositoryFactory::create();
        $snapshot = $this->make_snapshot('repository-reference');

        $repository->save($snapshot);

        $purchaserecord = $snapshot->get_purchase()->to_record();

        $loaded = $repository->find_by_reference(
            $purchaserecord->reference
        );

        $this->assertNotNull($loaded);

        $this->assertSame(
            $purchaserecord->purchaseuuid,
            $loaded->get_purchase()
                ->to_record()
                ->purchaseuuid
        );
    }

    public function test_repository_can_find_saved_purchase_by_legacy_identity(): void {
        $repository = CommercePurchaseSqlRepositoryFactory::create();
        $snapshot = $this->make_snapshot('repository-legacy');

        $repository->save($snapshot);

        $this->assertTrue(
            $repository->exists_for_legacy_reference(
                'subscription',
                701
            )
        );

        $loaded = $repository->find_by_legacy_reference(
            'subscription',
            701
        );

        $this->assertNotNull($loaded);
    }

    private function make_snapshot(
        string $suffix
    ): CommercePurchasePersistenceSnapshot {
        $purchaseuuid = md5('purchase-' . $suffix);

        $purchase = new CommercePurchaseRecord(
            $purchaseuuid,
            'cmp_' . substr(md5('reference-' . $suffix), 0, 24),
            'subscription',
            'subscription',
            701,
            null,
            'customer@example.com',
            'pending',
            'EUR',
            12000,
            0,
            12000,
            '{"email":"customer@example.com"}',
            '{"version":1}',
            '{"source":"phpunit"}',
            1,
            1721700000,
            1721800000
        );

        $item = new CommercePurchaseItemRecord(
            $purchaseuuid,
            0,
            'subscription',
            'plan-a1-full',
            'CampusFR A1',
            1,
            'EUR',
            12000,
            12000,
            0,
            12000,
            '{}',
            '{}',
            '{}'
        );

        return new CommercePurchasePersistenceSnapshot(
            $purchase,
            [$item],
            [],
            []
        );
    }
}