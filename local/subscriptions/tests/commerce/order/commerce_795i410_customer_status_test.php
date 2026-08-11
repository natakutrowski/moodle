<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\order\presentation\CommerceCustomerStatusResolver;

final class commerce_795i410_customer_status_test extends \advanced_testcase {
    public function test_none_is_never_exposed_as_customer_access_status(): void {
        $this->resetAfterTest();
        $resolver = new CommerceCustomerStatusResolver();
        $this->assertNull($resolver->resolve_access('None'));
    }

    public function test_technical_paid_status_is_presented_as_customer_label(): void {
        $this->resetAfterTest();
        $resolver = new CommerceCustomerStatusResolver();
        $result = $resolver->resolve_payment('paid');
        $this->assertSame(get_string('commerce_i410_payment_received', 'local_subscriptions'), $result['label']);
        $this->assertSame('success', $result['class']);
    }
}
