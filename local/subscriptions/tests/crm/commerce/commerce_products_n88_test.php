<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n88_test extends advanced_testcase {
    public function test_access_scope_page_uses_resolved_product_name(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/access_scope.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
        self::assertStringContainsString(
            '    $displayname,',
            $source
        );
        self::assertStringNotContainsString(
            'breadcrumb(' . "\n" . '    $product->get_name()',
            $source
        );
    }

    public function test_customer_access_is_primary_and_legacy_mapping_is_collapsed(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/access_scope.php'
        );

        foreach ([
            'commerce_access_scope_customer_access_title',
            'commerce_access_scope_courses_business_title',
            'commerce_access_scope_legacy_compatibility_title',
            'crm-product-access-scope-legacy-details',
            "html_writer::tag(\n    'details'",
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_access_scope_removes_technical_ids_from_plan_labels(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/access_scope.php'
        );

        self::assertStringContainsString(
            '$label = format_string($plan->name);',
            $source
        );
        self::assertStringNotContainsString(
            "format_string(\$plan->name) . ' (#'",
            $source
        );
    }

    public function test_linked_product_conflicts_use_business_name_resolver(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/access_scope.php'
        );

        self::assertStringContainsString(
            '$linkedlabel = $linkedproduct',
            $source
        );
        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
    }

    public function test_n88_keeps_existing_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
