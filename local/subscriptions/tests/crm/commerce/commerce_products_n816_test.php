<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n816_test extends advanced_testcase {
    public function test_builder_no_longer_exposes_raw_sku_recommendations(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/products/storefront_builder.php');
        self::assertStringNotContainsString("name' => 'storefront_recommendations'", $source);
        self::assertStringContainsString('commerce_storefront_n816_advanced_options', $source);
    }

    public function test_distribution_scopes_language_to_seo(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/products/storefront_distribution.php');
        self::assertStringContainsString('commerce_storefront_n816_seo_language', $source);
        self::assertStringContainsString('data-n814-language-switch', $source);
    }

    public function test_tools_explain_language_as_target(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/products/storefront_tools.php');
        self::assertStringContainsString('commerce_storefront_n816_tools_target_language', $source);
    }

    public function test_n816_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        self::assertStringContainsString('$plugin->version = 2026081602;', $version);
    }
}
