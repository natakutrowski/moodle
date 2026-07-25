<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
use local_subscriptions\commerce\rollout\CommerceRolloutGuard;
final class commerce_i10e_rollout_guard_test extends advanced_testcase {
    public function test_repair_requires_reconciliation(): void {
        $this->resetAfterTest();
        set_config('commerce_native_repair_enabled', 1, 'local_subscriptions');
        set_config('commerce_native_reconciliation_enabled', 0, 'local_subscriptions');
        $this->expectException(\RuntimeException::class);
        (new CommerceRolloutGuard())->assert_safe_configuration();
    }
    public function test_detection_only_configuration_is_safe(): void {
        $this->resetAfterTest();
        set_config('commerce_native_reconciliation_enabled', 1, 'local_subscriptions');
        set_config('commerce_native_repair_enabled', 0, 'local_subscriptions');
        (new CommerceRolloutGuard())->assert_safe_configuration();
        $this->addToAssertionCount(1);
    }
}
