<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\reconciliation\CommerceReconciliationPolicy;

final class commerce_i10d_reconciliation_test extends advanced_testcase {
    public function test_reconciliation_is_disabled_by_default(): void {
        $this->resetAfterTest();
        $policy = new CommerceReconciliationPolicy();
        $this->assertFalse($policy->is_enabled());
        $this->assertFalse($policy->repair_enabled());
    }
}
