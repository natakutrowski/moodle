<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n811_test extends advanced_testcase {
    public function test_bundle_preview_uses_business_names_and_hides_technical_data_by_default(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/preview.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
        self::assertStringContainsString(
            'CommercePresentationContext::CRM',
            $source
        );
        self::assertStringNotContainsString(
            "html_writer::tag('code', s(\$leaf->get_sku())",
            $source
        );
        self::assertStringContainsString(
            'crm-bundle-preview-tech-details',
            $source
        );
    }

    public function test_bundle_preview_has_no_redundant_back_to_products_button(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/preview.php'
        );

        self::assertStringNotContainsString(
            'commerce_back_to_products',
            $source
        );
    }

    public function test_bundle_preview_displays_effective_promotional_price(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/preview.php'
        );

        self::assertStringContainsString(
            'CommerceProductPromotionService',
            $source
        );
        self::assertStringContainsString(
            "promotion['amountminor']",
            $source
        );
        self::assertStringContainsString(
            "promotion['compareamountminor']",
            $source
        );
    }

    public function test_product_workflow_pages_render_commerce_products_navigation(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            'edit.php',
            'assets.php',
            'components.php',
            'prices.php',
            'pricing.php',
            'access_scope.php',
            'preview.php',
            'storefront.php',
        ] as $file) {
            $source = file_get_contents(
                $root . '/admin/commerce/products/' . $file
            );

            self::assertStringContainsString(
                'CommerceSectionNavigationRenderer::render(',
                $source,
                $file
            );
            self::assertStringContainsString(
                'CommerceSectionNavigationRenderer::PRODUCTS',
                $source,
                $file
            );
        }
    }

    public function test_n811_keeps_existing_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
