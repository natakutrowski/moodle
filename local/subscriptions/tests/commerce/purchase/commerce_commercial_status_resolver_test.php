<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\purchase\status\CommerceCommercialStatus;
use local_subscriptions\commerce\purchase\status\CommerceCommercialStatusResolver;

final class commerce_commercial_status_resolver_test extends \advanced_testcase {
    public function test_paid_purchase_waiting_for_fulfillment_is_to_fulfill(): void {
        $resolver = new CommerceCommercialStatusResolver();
        $this->assertSame(
            CommerceCommercialStatus::TO_FULFILL,
            $resolver->resolve('paid', ['paid'], ['pending'])
        );
    }

    public function test_all_fulfillments_completed_is_fulfilled(): void {
        $resolver = new CommerceCommercialStatusResolver();
        $this->assertSame(
            CommerceCommercialStatus::FULFILLED,
            $resolver->resolve('paid', ['paid'], ['completed', 'fulfilled'])
        );
    }

    public function test_refund_has_priority_over_fulfillment(): void {
        $resolver = new CommerceCommercialStatusResolver();
        $this->assertSame(
            CommerceCommercialStatus::REFUNDED,
            $resolver->resolve('completed', ['refunded'], ['fulfilled'])
        );
    }
}
