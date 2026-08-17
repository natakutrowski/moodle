<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n86_test extends advanced_testcase {
    public function test_assets_uses_resolved_name_for_breadcrumb_and_previews(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/assets.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
        self::assertStringContainsString(
            '\'productname\' => format_string($displayname)',
            $source
        );
        self::assertStringContainsString(
            'CommerceProductEditorNavigationRenderer::breadcrumb(',
            $source
        );
        self::assertStringContainsString(
            '    $displayname,',
            $source
        );
    }

    public function test_cover_save_and_delete_actions_share_one_row(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/assets.php'
        );
        $styles = file_get_contents(
            $root . '/styles/commerce_product_assets.css'
        );

        self::assertStringContainsString(
            'commerce-product-asset-card__button-row',
            $source
        );
        self::assertStringContainsString(
            'fa fa-trash-o me-1',
            $source
        );
        self::assertStringContainsString(
            '.commerce-product-asset-card__button-row',
            $styles
        );
    }

    public function test_digital_files_use_desktop_and_mobile_cards(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/assets.php'
        );

        foreach ([
            'crm-product-assets-digital-grid',
            'crm-product-assets-digital-card',
            '\'icon\' => \'fa-desktop\'',
            '\'icon\' => \'fa-mobile\'',
            'commerce_product_digital_file_ready',
            'commerce_product_digital_file_missing',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_n86_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        preg_match(
            '/\\$plugin->version\\s*=\\s*(\\d+);/',
            $version,
            $matches
        );
        self::assertGreaterThanOrEqual(
            2026081601,
            (int)($matches[1] ?? 0)
        );
    }
}
