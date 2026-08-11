<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_navigation_j10a1_test extends \advanced_testcase {
    public function test_customer_pages_link_back_to_mon_campus(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';

        foreach (['my_purchases.php', 'my_digital_products.php', 'order_details.php'] as $file) {
            $source = file_get_contents($root . '/' . $file);
            $this->assertIsString($source);
            $this->assertStringContainsString('UrlFactory::my_campus()', $source);
            $this->assertStringContainsString('$PAGE->navbar->add', $source);
        }
    }

    public function test_order_result_promotes_mon_campus(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('$mycampusurl = UrlFactory::my_campus();', $source);
        $this->assertStringContainsString('commerce_order_result_access_contents', $source);


    }

    public function test_profile_navigation_contains_mon_campus(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/lib.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('local_subscriptions_profile_campus', $source);
        $this->assertStringContainsString('UrlFactory::my_campus()', $source);
    }
}
