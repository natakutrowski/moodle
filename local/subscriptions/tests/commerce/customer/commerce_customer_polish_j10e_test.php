<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_customer_polish_j10e_test extends \advanced_testcase {
    public function test_customer_routes_and_hub_polish_are_present(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $cart = file_get_contents($root . '/cart.php');
        $urlfactory = file_get_contents($root . '/classes/url/UrlFactory.php');
        $this->assertStringContainsString("/local/subscriptions/commerce_checkout.php", $cart);
        $this->assertStringContainsString('function my_campus', $urlfactory);
        $this->assertStringContainsString('function my_purchases', $urlfactory);

    }
}
