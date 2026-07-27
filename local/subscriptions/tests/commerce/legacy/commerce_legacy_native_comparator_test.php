<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparison;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;

final class commerce_legacy_native_comparator_test extends advanced_testcase {
    public function test_comparator_reports_equal_and_missing_snapshots(): void {
        $snapshot = $this->make_snapshot();
        $comparator = new CommerceLegacyNativeComparator();

        $this->assertTrue($comparator->compare($snapshot, $snapshot)->is_equal());
        $this->assertSame(
            CommerceLegacyNativeComparison::STATUS_MISSING_NATIVE,
            $comparator->compare($snapshot, null)->get_status()
        );
    }

    private function make_snapshot(): CommercePurchasePersistenceSnapshot {
        $uuid = md5('legacy-comparator');
        return new CommercePurchasePersistenceSnapshot(
            new CommercePurchaseRecord(
                $uuid,
                'cmp_' . substr(md5('legacy-comparator-reference'), 0, 24),
                'subscription',
                'subscription',
                1,
                null,
                'test@example.com',
                'completed',
                'EUR',
                1000,
                0,
                1000,
                '{"b":2,"a":1}',
                '{}',
                '{}',
                1,
                1700000000,
                1700000001
            ),
            [new CommercePurchaseItemRecord(
                $uuid,
                0,
                'subscription',
                'plan:1',
                'Plan',
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
    }
}