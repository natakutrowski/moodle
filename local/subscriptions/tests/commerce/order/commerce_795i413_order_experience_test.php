<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\order\presentation\CommerceOrderExperienceResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderItemPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;

final class commerce_795i413_order_experience_test extends advanced_testcase {
    public function test_multi_product_cart_is_not_a_bundle(): void {
        $order = new CommerceOrderPresentation(
            1,
            'uuid',
            'cmp_test',
            'cart',
            2,
            'student@example.test',
            'EUR',
            3000,
            'paid',
            'paid',
            'completed',
            'stripe',
            1000,
            900,
            [
                new CommerceOrderItemPresentation('course', 'course_access', 'Course', 1, 'EUR', 2000, 2000, 0, 2000),
                new CommerceOrderItemPresentation('digital', 'digital_download', 'PDF', 1, 'EUR', 1000, 1000, 0, 1000),
            ],
            []
        );

        $experience = (new CommerceOrderExperienceResolver())->resolve($order);

        $this->assertFalse($experience['isbundle']);
        $this->assertTrue($experience['ismultiproduct']);
    }

    public function test_catalog_bundle_remains_a_bundle(): void {
        $order = new CommerceOrderPresentation(
            1,
            'uuid',
            'cmp_bundle',
            'bundle',
            2,
            'student@example.test',
            'EUR',
            3000,
            'paid',
            'paid',
            'completed',
            'stripe',
            1000,
            900,
            [new CommerceOrderItemPresentation('bundle', 'bundle', 'Bundle', 1, 'EUR', 3000, 3000, 0, 3000)],
            []
        );

        $experience = (new CommerceOrderExperienceResolver())->resolve($order);

        $this->assertTrue($experience['isbundle']);
        $this->assertFalse($experience['ismultiproduct']);
    }
}
