<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_routes_j10b_test extends \advanced_testcase {
    public function test_route_registry_exposes_localised_customer_routes(): void {
        $this->assertSame('/mon-campus', subscription_config::public_route_path('my_campus', 'fr'));
        $this->assertSame('/my-campus', subscription_config::public_route_path('my_campus', 'en'));
        $this->assertSame('/moi-pokupki', subscription_config::public_route_path('my_purchases', 'ru'));
    }

    public function test_router_and_product_slug_contract_are_present(): void {
        $root = dirname(__DIR__, 3);
        $router = file_get_contents($root . '/public_router.php');
        $editor = file_get_contents($root . '/admin/commerce/products/storefront.php');
        $this->assertIsString($router);
        $this->assertStringContainsString('CommerceProductSlugService', $router);
        $this->assertIsString($editor);
        $this->assertStringContainsString('storefront_route_slug_fr', $editor);
    }
}
