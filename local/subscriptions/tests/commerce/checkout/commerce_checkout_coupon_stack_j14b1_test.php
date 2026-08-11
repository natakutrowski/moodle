<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J14B.1 regression tests for stacked catalogue and checkout discounts. */
final class commerce_checkout_coupon_stack_j14b1_test extends \advanced_testcase {
    public function test_breakdown_accepts_product_promotion_and_checkout_adjustment(): void {
        $pricing = new \local_subscriptions\commerce\pricing\CommerceCommercialPriceBreakdown(
            'EUR', 1, 20000, 3000, 17000, 0, 17000, 0, 13600, 15, 0, '', null, null, 3400
        );
        $this->assertSame(3400, $pricing->get_adjustment_discount_total_minor());
        $this->assertSame(13600, $pricing->get_final_total_minor());
    }
}
