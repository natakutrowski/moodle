<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\order\presentation\CommercePostPaymentStateResolver;

/** @covers \local_subscriptions\commerce\order\presentation\CommercePostPaymentStateResolver */
final class commerce_795i2_post_payment_state_test extends advanced_testcase {
    public function test_paid_order_with_access_is_successful(): void {
        $order = $this->order('paid', true);
        $state = (new CommercePostPaymentStateResolver())->resolve($order, 'success');
        $this->assertSame('success', $state->code);
        $this->assertTrue($state->showaccesses);
    }

    public function test_cancelled_browser_return_remains_retryable(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve($this->order('pending', false), 'cancel');
        $this->assertSame('cancelled', $state->code);
        $this->assertTrue($state->canretry);
    }

    public function test_paid_order_overrides_stale_failure_browser_return(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve($this->order('paid', true), 'failure');
        $this->assertSame('success', $state->code);
        $this->assertFalse($state->canretry);
        $this->assertTrue($state->showaccesses);
    }

    public function test_paid_order_overrides_stale_cancel_browser_return(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve($this->order('paid', true), 'cancel');
        $this->assertSame('success', $state->code);
        $this->assertFalse($state->canretry);
        $this->assertTrue($state->showaccesses);
    }

    public function test_success_browser_return_hides_stale_failed_snapshot_while_reconciling(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve($this->order('failed', false), 'success');
        $this->assertSame('pending', $state->code);
        $this->assertFalse($state->canretry);
        $this->assertFalse($state->showaccesses);
    }

    public function test_explicit_failure_browser_return_still_shows_failure(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve($this->order('failed', false), 'failure');
        $this->assertSame('failed', $state->code);
        $this->assertTrue($state->canretry);
    }

    private function order(string $paymentstatus, bool $available): CommerceOrderPresentation {
        $access = new class($available) {
            public function __construct(public bool $available) {}
        };
        $item = new class($access) {
            public array $accesses;
            public function __construct(object $access) { $this->accesses = [$access]; }
        };
        return new CommerceOrderPresentation(1, 'uuid', 'cmp_test', 'course', 2, 'x@example.test', 'EUR', 1000,
            'active', $paymentstatus, 'completed', 'stripe', time(), time(), [$item], [], []);
    }
}
