<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyRepository;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyService;

final class commerce_i10d_idempotency_test extends advanced_testcase {
    public function test_completed_operation_is_replayed_without_second_execution(): void {
        $this->resetAfterTest(true);

        $service = new CommerceIdempotencyService(
            new CommerceIdempotencyRepository()
        );
        $calls = 0;

        $first = $service->execute('phpunit', 'same-key', ['id' => 7], function() use (&$calls): array {
            $calls++;
            return ['status' => 'ok'];
        });
        $second = $service->execute('phpunit', 'same-key', ['id' => 7], function() use (&$calls): array {
            $calls++;
            return ['status' => 'unexpected'];
        });

        $this->assertFalse($first['replayed']);
        $this->assertTrue($second['replayed']);
        $this->assertSame(['status' => 'ok'], $second['result']);
        $this->assertSame(1, $calls);
    }

    public function test_same_key_rejects_different_payload(): void {
        $this->resetAfterTest(true);

        $service = new CommerceIdempotencyService(
            new CommerceIdempotencyRepository()
        );
        $service->execute('phpunit', 'conflict-key', ['id' => 7], fn(): array => ['status' => 'ok']);

        $this->expectException(\RuntimeException::class);
        $service->execute('phpunit', 'conflict-key', ['id' => 8], fn(): array => ['status' => 'ok']);
    }
}
