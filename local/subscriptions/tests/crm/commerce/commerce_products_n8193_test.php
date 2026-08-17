<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n8193_test extends advanced_testcase {
    public function test_builder_header_uses_page_boutique_and_resolved_product_name(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            "'commerce_storefront_n819_tab_builder'",
            $source
        );
        self::assertStringContainsString(
            ") . ' — ' . \$displayname",
            $source
        );
        self::assertStringContainsString(
            "'commerce_product_step_storefront'",
            $source
        );
    }

    public function test_builder_subnavigation_is_rendered_below_page_header(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        $header = strpos(
            $source,
            'CommerceProductPageHeaderRenderer::render('
        );
        $tabs = strpos(
            $source,
            "html_writer::start_div('commerce-storefront-n812-tabs')"
        );

        self::assertNotFalse($header);
        self::assertNotFalse($tabs);
        self::assertGreaterThan($header, $tabs);
    }

    public function test_builder_still_uses_product_name_resolver(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
        self::assertStringContainsString(
            '$displayname',
            $source
        );
    }

    public function test_n8193_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
