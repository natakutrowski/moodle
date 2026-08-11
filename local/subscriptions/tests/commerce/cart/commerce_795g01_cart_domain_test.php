<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\cart;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\policy\CommerceRangeQuantityPolicy;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;

final class commerce_795g01_cart_domain_test extends \advanced_testcase {
    public function test_cart_serialisation_keeps_only_stable_catalogue_references(): void {
        $cart = new CommerceCart(
            str_repeat('a', 32),
            42,
            'eur',
            [new CommerceCartItem('course-a1', 15, 1)]
        );

        $restored = CommerceCart::from_array($cart->to_array());
        $item = $restored->get_items()[0];

        $this->assertSame('EUR', $restored->get_currency());
        $this->assertSame('COURSE-A1', $item->get_product_sku());
        $this->assertSame(15, $item->get_price_id());
        $this->assertArrayNotHasKey('name', $item->to_array());
        $this->assertArrayNotHasKey('unitprice', $item->to_array());
    }

    public function test_single_policy_rejects_more_than_one_unit(): void {
        $policy = new CommerceSingleQuantityPolicy();

        $this->assertTrue($policy->allows(1));
        $this->assertFalse($policy->allows(2));
    }

    public function test_range_policy_keeps_future_multiple_quantities_possible(): void {
        $policy = new CommerceRangeQuantityPolicy(2, 10, 2);

        $this->assertTrue($policy->allows(2));
        $this->assertTrue($policy->allows(6));
        $this->assertFalse($policy->allows(5));
        $this->assertFalse($policy->allows(12));
    }
}
