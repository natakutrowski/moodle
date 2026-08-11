<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\readiness\CommerceBackfillReadinessAuditor;

final class commerce_795f8c_backfill_readiness_test extends advanced_testcase {
    public function test_unknown_family_is_rejected_without_writes(): void {
        global $DB;
        $this->resetAfterTest();
        $before = $DB->count_records('local_subscriptions_commerce_purchase');
        $data = (new CommerceBackfillReadinessAuditor($DB))->audit('not-a-family', 10)->to_array();
        $after = $DB->count_records('local_subscriptions_commerce_purchase');

        $this->assertSame($before, $after);
        $this->assertFalse($data['certifiable']);
        $this->assertSame('unknown_legacy_family', $data['issues'][0]['code']);
    }

    public function test_auditor_exposes_bounded_diagnostic_inventories(): void {
        global $DB;
        $this->resetAfterTest();

        $data = (new CommerceBackfillReadinessAuditor($DB))->audit('all', 10)->to_array();

        $this->assertTrue($data['inventory']['readonly']);
        $this->assertSame(25, $data['inventory']['detail_limit']);
        $this->assertArrayHasKey('missing_native_details', $data['inventory']);
        $this->assertArrayHasKey('different_snapshot_details', $data['inventory']);
        $this->assertArrayHasKey('invalid_legacy_details', $data['inventory']);
    }
    public function test_runtime_metadata_paths_are_ignored_but_business_paths_are_not(): void {
        global $DB;
        $this->resetAfterTest();

        $auditor = new CommerceBackfillReadinessAuditor($DB);
        $method = new \ReflectionMethod($auditor, 'is_ignored_runtime_metadata_path');

        $this->assertTrue($method->invoke($auditor, 'purchase.metadatajson.fulfillment_resolution'));
        $this->assertTrue($method->invoke($auditor, 'purchase.metadatajson.runtime_source'));
        $this->assertFalse($method->invoke($auditor, 'purchase.status'));
        $this->assertFalse($method->invoke($auditor, 'purchase.metadatajson.customer_reference'));
    }

}
