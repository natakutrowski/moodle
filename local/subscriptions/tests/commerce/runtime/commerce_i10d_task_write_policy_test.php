<?php

declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;
use local_subscriptions\commerce\command\policy\CommerceWritePolicy;
final class commerce_i10d_task_write_policy_test extends advanced_testcase {
    public function test_task_writes_are_disabled_by_default(): void {
        $this->resetAfterTest();
        $this->assertFalse((new CommerceWritePolicy())->native_dual_write_enabled('task'));
    }
}
