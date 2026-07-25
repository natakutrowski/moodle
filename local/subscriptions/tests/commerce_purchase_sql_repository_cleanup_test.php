<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

final class commerce_purchase_sql_repository_cleanup_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_delete_by_legacy_reference_removes_complete_aggregate(): void {
        global $DB;
        $repository = CommercePurchaseSqlRepositoryFactory::create();
        $uuid = md5('cleanup-test');
        $snapshot = new CommercePurchasePersistenceSnapshot(
            new CommercePurchaseRecord(
                $uuid,
                'cmp_' . substr(md5('cleanup-reference'), 0, 24),
                'subscription',
                'subscription',
                8123,
                null,
                'cleanup@example.com',
                'pending',
                'EUR',
                1000,
                0,
                1000,
                '{}',
                '{}',
                '{}',
                1,
                1721700000,
                1721700000
            ),
            [new CommercePurchaseItemRecord(
                $uuid,
                0,
                'subscription',
                'plan:cleanup',
                'Cleanup plan',
                1,
                'EUR',
                1000,
                1000,
                0,
                1000,
                '{}',
                '{}',
                '{}'
            )],
            [],
            []
        );
        $repository->save($snapshot);

        $this->assertTrue($repository->delete_by_legacy_reference('subscription', 8123));
        $this->assertFalse($repository->exists_for_legacy_reference('subscription', 8123));
        $this->assertSame(0, $DB->count_records(CommercePersistenceSchema::TABLE_ITEM));
    }
}
