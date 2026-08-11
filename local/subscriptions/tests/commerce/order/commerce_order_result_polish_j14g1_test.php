<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\order;

defined('MOODLE_INTERNAL') || die();

final class commerce_order_result_polish_j14g1_test extends \advanced_testcase {
    public function test_order_result_contains_store_action_and_account_spacing_hook(): void {
        $source = file_get_contents(__DIR__ . '/../../../order_result.php');
        $css = file_get_contents(__DIR__ . '/../../../styles/order_result.css');

        $this->assertIsString($source);
        $this->assertStringContainsString('UrlFactory::storefront()', $source);
        $this->assertStringContainsString('commerce-order-next__store', $source);
        $this->assertStringContainsString('commerce-order-account-ready', $source);
        $this->assertStringContainsString('commerce_order_result_discover_store', $source);

        $this->assertIsString($css);
        $this->assertStringContainsString('.commerce-order-account-ready', $css);
        $this->assertStringContainsString('.commerce-order-next__store', $css);
    }
}
