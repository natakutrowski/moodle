<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\order\presentation\CommerceOrderAccessPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderExperienceResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderItemPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;

/** @covers \local_subscriptions\commerce\order\presentation\CommerceOrderExperienceResolver */
final class commerce_795i48_order_experience_test extends advanced_testcase {
    public function test_mixed_native_order_is_presented_as_bundle(): void {
        $course = new CommerceOrderItemPresentation('course', 'course_access', 'Course', 1, 'EUR', 1000, 1000, 0, 1000, [
            new CommerceOrderAccessPresentation('course_access', 'Open', 'active', true, '/course/view.php?id=1', 'grant-1'),
        ]);
        $digital = new CommerceOrderItemPresentation('digital', 'digital_download', 'PDF', 1, 'EUR', 500, 500, 0, 500);
        $order = new CommerceOrderPresentation(1, 'uuid', 'CMP-1', 'bundle', 2, 'user@example.com', 'EUR', 1500,
            'paid', 'paid', 'completed', 'stripe', 100, 90, [$course, $digital], []);

        $result = (new CommerceOrderExperienceResolver())->resolve($order);

        $this->assertTrue($result['isbundle']);
        $this->assertSame(2, $result['itemcount']);
        $this->assertSame(1, $result['coursecount']);
        $this->assertSame(1, $result['digitalcount']);
        $this->assertSame(1, $result['accesscount']);
    }
}
