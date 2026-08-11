<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_customer_improvements_j10d_test extends \advanced_testcase {
    public function test_typed_product_routes_and_friendly_hub_links_are_present(): void {
        global $CFG;
        $config = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');
        $hub = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/customer/hub/CommerceCustomerHubService.php');
        $this->assertStringContainsString('PUBLIC_PRODUCT_ROUTES', $config);
        $this->assertStringContainsString('public_product_path', $config);
        $this->assertStringContainsString('UrlFactory::my_courses()', $hub);
        $this->assertStringContainsString('course_progress', $hub);
    }
}
