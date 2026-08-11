<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;

final class commerce_showroom_pending_products_j13b_test extends \advanced_testcase {
    public function test_temporary_skus_are_isolated_in_registry(): void {
        $definition = \local_subscriptions\commerce\showroom\CommerceShowroomRegistry::require(
            \local_subscriptions\commerce\showroom\CommerceShowroomRegistry::THIRD_GROUP_VERBS
        );
        $products = $definition->get_products();
        self::assertSame('COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE', $products['course']);
        self::assertSame('DIGITAL.VERBES-3E-GROUPE', $products['pdf']);
        self::assertSame('BUNDLE.THIRD_GROUP_VERBS_BUNDLE', $products['bundle']);

    }
}
