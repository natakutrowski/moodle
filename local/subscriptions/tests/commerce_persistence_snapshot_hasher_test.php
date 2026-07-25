<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\certification\CommercePersistenceSnapshotHasher;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;

final class commerce_persistence_snapshot_hasher_test extends advanced_testcase {
    public function test_hash_is_stable_for_equivalent_json_objects(): void {
        $first = $this->snapshot('{"campaign":"summer","nested":{"b":2,"a":1}}');
        $second = $this->snapshot('{"nested":{"a":1,"b":2},"campaign":"summer"}');

        $hasher = new CommercePersistenceSnapshotHasher();
        $this->assertSame($hasher->hash($first), $hasher->hash($second));
    }

    public function test_hash_changes_when_business_data_changes(): void {
        $first = $this->snapshot('{"campaign":"summer"}', 12000);
        $second = $this->snapshot('{"campaign":"summer"}', 13000);

        $hasher = new CommercePersistenceSnapshotHasher();
        $this->assertNotSame($hasher->hash($first), $hasher->hash($second));
    }

    private function snapshot(string $metadatajson, int $totalminor = 12000): CommercePurchasePersistenceSnapshot {
        $uuid = md5('certification-hash');
        $purchase = new CommercePurchaseRecord(
            $uuid,
            'cmp_' . substr(md5('certification-reference'), 0, 24),
            'subscription',
            'subscription',
            9001,
            null,
            'hash@example.com',
            'pending',
            'EUR',
            $totalminor,
            0,
            $totalminor,
            '{}',
            '{}',
            $metadatajson,
            1,
            1721700000,
            1721700000
        );
        $item = new CommercePurchaseItemRecord(
            $uuid,
            0,
            'subscription',
            'plan:test',
            'Test plan',
            1,
            'EUR',
            $totalminor,
            $totalminor,
            0,
            $totalminor,
            '{}',
            '{}',
            '{}'
        );
        return new CommercePurchasePersistenceSnapshot($purchase, [$item], [], []);
    }
}
