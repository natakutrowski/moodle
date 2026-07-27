<?php

declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;
use local_subscriptions\commerce\command\dto\CommerceCommandRequest;
use local_subscriptions\commerce\command\dto\CommerceCommandResult;
use local_subscriptions\commerce\command\observability\CommerceWriteObserver;
final class commerce_i10d_observability_test extends advanced_testcase {
    public function test_successful_writes_are_silent(): void {
        $this->resetAfterTest();
        (new CommerceWriteObserver())->observe(new CommerceCommandRequest('digital', 1, 'test'), new CommerceCommandResult('digital', 1, 'unchanged'), 1);
        $this->assertDebuggingNotCalled();
    }
}
