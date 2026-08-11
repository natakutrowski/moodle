<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_j12a_test extends \advanced_testcase {
    public function test_catalog_filters_are_collapsible(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/storefront/catalog.mustache');
        self::assertStringContainsString('commerce-storefront__filters-shell', $template);
        self::assertStringContainsString('<details', $template);
    }

    public function test_storefront_back_link_is_in_commerce_panel(): void {
        $panel = file_get_contents(__DIR__ . '/../../../templates/storefront/product_commerce_panel.mustache');
        self::assertStringContainsString('commerce-product-commerce-panel__back', $panel);
        $course = file_get_contents(__DIR__ . '/../../../templates/storefront/product_templates/course.mustache');
        self::assertStringNotContainsString('btn btn-link px-0 mb-3', $course);
    }

    public function test_router_falls_back_to_mon_campus_for_authenticated_customer(): void {
        $router = file_get_contents(__DIR__ . '/../../../public_router.php');
        self::assertStringContainsString('CommerceRouteRegistry::MY_PROFILE', $router);
        self::assertStringContainsString('require_login()', $router);
        self::assertStringContainsString("/user/profile.php", $router);


    }
}
