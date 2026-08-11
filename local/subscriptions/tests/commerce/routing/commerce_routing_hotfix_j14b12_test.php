<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_routing_hotfix_j14b12_test extends \advanced_testcase {
    public function test_checkout_uses_smart_legal_urls(): void {
        $source = file_get_contents(__DIR__ . '/../../../commerce_checkout.php');
        self::assertIsString($source);
        self::assertStringContainsString("new moodle_url('/privacy')", $source);
        self::assertStringContainsString("new moodle_url('/terms')", $source);
        self::assertStringNotContainsString("\$policyurls['policy']", $source);
    }

    public function test_activation_redirect_has_no_duplicate_notification(): void {
        $source = file_get_contents(__DIR__ . '/../../../guest_account_activate.php');
        self::assertIsString($source);
        self::assertStringContainsString('redirect($destination);', $source);
        self::assertStringNotContainsString('commerce_guest_activation_success', $source);
    }

    public function test_product_router_falls_back_to_unique_slug(): void {
        $source = file_get_contents(__DIR__ . '/../../../public_router.php');
        self::assertIsString($source);
        self::assertStringContainsString("if (\$sku === null && \$category !== '')", $source);
        self::assertStringNotContainsString('UrlFactory::my_campus()', $source);
    }
}
