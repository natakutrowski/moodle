<?php

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\certification\CommerceOrphanFulfillmentCleaner;

/**
 * Tests for the 7.95F7A.1 orphan fulfillment cleanup.
 *
 * @covers \local_subscriptions\commerce\certification\CommerceOrphanFulfillmentCleaner
 */
final class commerce_795f7a1_orphan_fulfillment_cleanup_test extends advanced_testcase {
    public function test_dry_run_detects_orphan_without_deleting_it(): void {
        global $DB;

        $this->resetAfterTest(true);
        $id = $this->insert_orphan_fulfillment(900001);

        $cleaner = new CommerceOrphanFulfillmentCleaner($DB);
        $rows = $cleaner->inspect();

        $match = array_values(array_filter($rows, static fn(array $row): bool => $row['id'] === $id));
        $this->assertCount(1, $match);
        $this->assertTrue($match[0]['safe_to_delete']);
        $this->assertTrue($DB->record_exists('local_subscriptions_commerce_fulfillment', ['id' => $id]));
    }

    public function test_execute_deletes_safe_orphan(): void {
        global $DB;

        $this->resetAfterTest(true);
        $id = $this->insert_orphan_fulfillment(900002);

        $cleaner = new CommerceOrphanFulfillmentCleaner($DB);
        $result = $cleaner->execute();

        $this->assertContains($id, $result['deletedids']);
        $this->assertFalse($DB->record_exists('local_subscriptions_commerce_fulfillment', ['id' => $id]));
    }

    public function test_execute_skips_orphan_with_purchase_item_dependency(): void {
        global $DB;

        $this->resetAfterTest(true);
        $purchaseid = 900003;
        $id = $this->insert_orphan_fulfillment($purchaseid);

        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $purchaseid,
            'position' => 0,
            'itemtype' => 'course_access',
            'itemreference' => 'test-item',
            'label' => 'Test item',
            'quantity' => 1,
            'currency' => 'EUR',
            'unitminor' => 1000,
            'grossminor' => 1000,
            'discountminor' => 0,
            'netminor' => 1000,
            'pricingjson' => '{}',
            'fulfillmentjson' => '{}',
            'metadatajson' => '{}',
        ]);

        $cleaner = new CommerceOrphanFulfillmentCleaner($DB);
        $result = $cleaner->execute();

        $this->assertContains($id, $result['skippedids']);
        $this->assertTrue($DB->record_exists('local_subscriptions_commerce_fulfillment', ['id' => $id]));
    }

    private function insert_orphan_fulfillment(int $purchaseid): int {
        global $DB;

        return (int)$DB->insert_record('local_subscriptions_commerce_fulfillment', (object)[
            'purchaseid' => $purchaseid,
            'sequence' => 0,
            'reference' => 'test-reference-' . $purchaseid,
            'fulfillmentkey' => 'course_access',
            'idempotencykey' => 'test-idempotency-' . $purchaseid,
            'status' => 'completed',
            'metadatajson' => '{}',
        ]);
    }
}
