<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n812_test extends advanced_testcase {
    public function test_storefront_entry_is_split_into_four_business_areas(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront.php'
        );

        foreach ([
            "'content'",
            "'presentation'",
            "'distribution'",
            "'tools'",
            'commerce-storefront-n812-tabs',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_storefront_hub_uses_product_name_resolver(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront.php'
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

    public function test_existing_full_editor_is_preserved_as_transitional_builder(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            'CommerceStorefrontVisualBuilderService',
            $source
        );
        self::assertStringContainsString(
            "'data-region' => 'storefront-builder-form'",
            $source
        );
        self::assertStringContainsString(
            'commerce_storefront_n812_back_to_hub',
            $source
        );
        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
    }

    public function test_n812_storefront_audit_documents_runtime_consumers(): void {
        $root = dirname(__DIR__, 3);
        $audit = file_get_contents(
            $root . '/docs/commerce/storefront-admin-audit-n812.md'
        );

        foreach ([
            'CommerceStorefrontPageResolver',
            'CommerceStorefrontExperienceResolver',
            'CommerceStorefrontSeoPresenter',
            'CommerceProductDiscoveryUrlResolver',
            'N8.13',
        ] as $needle) {
            self::assertStringContainsString($needle, $audit);
        }
    }

    public function test_n812_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
