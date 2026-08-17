<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n8172_test extends advanced_testcase {
    public function test_builder_name_resolver_uses_product_name_as_fallback(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/commerce/products/storefront_builder.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "(int)\$product->get_id(),\n    \$product->get_name()",
            $source
        );
        self::assertStringNotContainsString(
            "(int)\$product->get_id(),\n    \$displayname",
            $source
        );
    }

    public function test_builder_header_uses_resolved_name(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/commerce/products/storefront_builder.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "    \$displayname\n);",
            $source
        );
    }

    public function test_n8172_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
