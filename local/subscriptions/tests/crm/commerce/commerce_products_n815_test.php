<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n815_test extends advanced_testcase {
    public function test_presentation_has_dedicated_screen_and_service(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/products/storefront_presentation.php'
        );
        $service = file_get_contents(
            $root . '/classes/commerce/storefront/admin/CommerceStorefrontPresentationService.php'
        );

        self::assertStringContainsString(
            'commerce-storefront-n815-layout-grid',
            $page
        );
        self::assertStringContainsString(
            'commerce-storefront-n815-position-grid',
            $page
        );
        self::assertStringContainsString(
            'CommerceStorefrontPresentationService',
            $page
        );
        self::assertStringContainsString(
            "\$metadata['storefront'] = \$storefront;",
            $service
        );
        self::assertStringNotContainsString(
            "['sections'] =",
            $service
        );
    }

    public function test_tools_have_dedicated_screen(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/products/storefront_tools.php'
        );

        foreach ([
            'CommerceStorefrontPackageService',
            'CommerceStorefrontLocaleTransferService',
            'CommerceStorefrontAiTranslationService',
            'CommerceStorefrontResetService',
            'commerce-storefront-n815-tools-grid',
        ] as $needle) {
            self::assertStringContainsString($needle, $page);
        }
    }

    public function test_hub_routes_presentation_distribution_and_tools_to_dedicated_pages(): void {
        $root = dirname(__DIR__, 3);
        $hub = file_get_contents(
            $root . '/admin/commerce/products/storefront.php'
        );

        foreach ([
            'storefront_presentation.php',
            'storefront_distribution.php',
            'storefront_tools.php',
        ] as $needle) {
            self::assertStringContainsString($needle, $hub);
        }
    }

    public function test_builder_defaults_to_content_focus(): void {
        $root = dirname(__DIR__, 3);
        $builder = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            "optional_param('area', 'content', PARAM_ALPHA)",
            $builder
        );
        self::assertStringContainsString(
            'if (!$contentfocus) {',
            $builder
        );
    }

    public function test_all_new_storefront_admin_screens_use_product_name_resolver(): void {
        $root = dirname(__DIR__, 3);
        foreach ([
            'storefront_presentation.php',
            'storefront_distribution.php',
            'storefront_tools.php',
        ] as $file) {
            $source = file_get_contents(
                $root . '/admin/commerce/products/' . $file
            );
            self::assertStringContainsString(
                'CommerceCatalogProductNameResolver::resolve_native_id(',
                $source,
                $file
            );
        }
    }

    public function test_n815_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
