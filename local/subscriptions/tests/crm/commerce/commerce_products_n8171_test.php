<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n8171_test extends advanced_testcase {
    public function test_builder_uses_resolved_product_name_in_header(): void {
        $source = file_get_contents(
            __DIR__
            . '/../../../admin/commerce/products/storefront_builder.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
        self::assertStringContainsString(
            'CommerceProductPageHeaderRenderer::render(',
            $source
        );
        self::assertStringContainsString(
            ") . ' — ' . \$displayname",
            $source
        );
    }

    public function test_builder_defines_type_options_before_picker(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/commerce/products/storefront_builder.php'
        );

        self::assertIsString($source);
        $definition = strpos($source, '$typeoptions = [');
        $picker = strpos($source, '$n813types = [');

        self::assertNotFalse($definition);
        self::assertNotFalse($picker);
        self::assertLessThan($picker, $definition);
    }

    public function test_builder_does_not_post_raw_recommendation_skus(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/commerce/products/storefront_builder.php'
        );

        self::assertIsString($source);
        self::assertStringNotContainsString(
            "name' => 'storefront_recommendations'",
            $source
        );
        self::assertStringContainsString(
            "\$currentdefinition['recommendations']",
            $source
        );
    }

    public function test_n8171_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
