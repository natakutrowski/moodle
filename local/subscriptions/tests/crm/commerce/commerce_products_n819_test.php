<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n819_test extends advanced_testcase {
    public function test_builder_is_in_page_boutique_subnavigation(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            'storefront.php',
            'storefront_builder.php',
            'storefront_presentation.php',
            'storefront_distribution.php',
            'storefront_tools.php',
        ] as $file) {
            $source = file_get_contents(
                $root . '/admin/commerce/products/' . $file
            );

            self::assertStringContainsString(
                'commerce_storefront_n819_tab_builder',
                $source,
                $file
            );
            self::assertStringContainsString(
                'storefront_builder.php',
                $source,
                $file
            );
        }
    }

    public function test_builder_has_no_back_link_or_global_preview_switcher(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringNotContainsString(
            'commerce-storefront-n812-builder-backbar',
            $source
        );
        self::assertStringNotContainsString(
            'commerce-storefront-preview-toolbar card card-body',
            $source
        );
        self::assertStringContainsString(
            'storefront-block-preview-switcher',
            $source
        );
    }

    public function test_preview_device_is_scoped_to_each_block(): void {
        $root = dirname(__DIR__, 3);
        $js = file_get_contents(
            $root . '/amd/src/storefront_builder_drag_drop.js'
        );

        self::assertStringContainsString(
            'const setPreviewDevice = function(card, device)',
            $js
        );
        self::assertStringContainsString(
            'card.querySelectorAll(',
            $js
        );
        self::assertStringNotContainsString(
            'local_subscriptions_storefront_preview',
            $js
        );
    }

    public function test_builder_has_dedicated_no_padding_page_class(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );
        $css = file_get_contents(
            $root . '/styles/storefront_builder.css'
        );

        self::assertStringContainsString(
            'local-subscriptions-commerce-product-storefront-builder-page',
            $page
        );
        self::assertStringContainsString(
            'padding-inline: 0 !important',
            $css
        );
    }

    public function test_n819_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
