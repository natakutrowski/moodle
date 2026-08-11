<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyRepository;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyService;

final class commerce_795h62_idempotency_guard_test extends advanced_testcase {
    public function test_recovery_operation_is_executed_once_and_then_replayed(): void {
        $this->resetAfterTest(true);
        $service = new CommerceIdempotencyService(new CommerceIdempotencyRepository());
        $calls = 0;
        $operation = function () use (&$calls): array {
            $calls++;
            return ['executed_actions' => ['complete_fulfillment']];
        };

        $first = $service->execute('checkout_recovery', 'recover:abc', ['purchaseuuid' => 'abc'], $operation);
        $second = $service->execute('checkout_recovery', 'recover:abc', ['purchaseuuid' => 'abc'], $operation);

        $this->assertFalse($first['replayed']);
        $this->assertTrue($second['replayed']);
        $this->assertSame(1, $calls);
        $this->assertSame($first['result'], $second['result']);
    }
}
