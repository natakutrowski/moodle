<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n814_test extends advanced_testcase {
    public function test_distribution_has_dedicated_business_screen(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/products/storefront_distribution.php'
        );

        foreach ([
            'CommerceStorefrontDistributionService',
            'commerce-storefront-n814-choice-grid',
            'commerce_storefront_n814_destination_storefront',
            'commerce_storefront_n814_destination_showroom',
            'commerce-storefront-n814-seo-preview',
        ] as $needle) {
            self::assertStringContainsString($needle, $page);
        }
    }

    public function test_distribution_uses_resolved_product_names_for_recommendations(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/products/storefront_distribution.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $page
        );
        self::assertStringContainsString(
            '$productoptions[$candidate->get_sku()]',
            $page
        );
        self::assertStringNotContainsString(
            "name=\"recommendations\"",
            $page
        );
    }

    public function test_distribution_service_preserves_other_storefront_domains(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/storefront/admin/CommerceStorefrontDistributionService.php'
        );

        self::assertStringContainsString(
            "\$storefront = is_array(\$metadata['storefront']",
            $source
        );
        self::assertStringContainsString(
            "\$metadata['storefront'] = \$storefront;",
            $source
        );
        self::assertStringNotContainsString(
            "unset(\$storefront['sections'])",
            $source
        );
        self::assertStringContainsString(
            'Deliberately preserve mediaitemid',
            $source
        );
    }

    public function test_auto_reassurance_is_represented_by_absent_override(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/storefront/admin/CommerceStorefrontDistributionService.php'
        );

        self::assertStringContainsString(
            "unset(\$experience['trust']);",
            $source
        );
        self::assertStringContainsString(
            "if (\$trustmode === 'custom')",
            $source
        );
    }

    public function test_auto_seo_removes_local_override_and_keeps_runtime_fallback(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/storefront/admin/CommerceStorefrontDistributionService.php'
        );

        self::assertStringContainsString(
            "unset(\$storefront['locales'][\$language]['seo']);",
            $source
        );
        self::assertStringContainsString(
            "if (\$seomode === 'custom')",
            $source
        );
    }

    public function test_storefront_hub_routes_distribution_to_n814_screen(): void {
        $root = dirname(__DIR__, 3);
        $hub = file_get_contents(
            $root . '/admin/commerce/products/storefront.php'
        );

        self::assertStringContainsString(
            '/local/subscriptions/admin/commerce/products/storefront_distribution.php',
            $hub
        );
    }

    public function test_n814_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
