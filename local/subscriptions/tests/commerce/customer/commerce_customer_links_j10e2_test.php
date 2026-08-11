<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_links_j10e2_test extends \advanced_testcase {
    public function test_customer_surfaces_use_public_routes(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions';
        $storefront = file_get_contents(
            $root
                . '/classes/commerce/storefront/presentation/'
                . 'CommerceStorefrontUrlResolver.php'
        );
        $access = file_get_contents(
            $root
                . '/classes/commerce/order/presentation/'
                . 'CommerceOrderAccessActionResolver.php'
        );
        $purchases = file_get_contents($root . '/my_purchases.php');
        $hub = file_get_contents(
            $root
                . '/classes/commerce/customer/hub/'
                . 'CommerceCustomerHubService.php'
        );

        $this->assertIsString($storefront);
        $this->assertStringContainsString(
            'UrlFactory::course(',
            $storefront
        );
        $this->assertIsString($access);
        $this->assertStringContainsString(
            'UrlFactory::course($courseid)',
            $access
        );
        $this->assertIsString($purchases);
        $this->assertStringContainsString(
            'CommerceCustomerPublicUrlResolver::product(',
            $purchases
        );
        $this->assertIsString($hub);
        $this->assertStringContainsString(
            'MyCourseImageService',
            $hub
        );
        $this->assertStringContainsString(
            'get_course_overviewfiles()',
            $hub
        );
    }
}
