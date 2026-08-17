<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n813_test extends advanced_testcase {
    public function test_builder_fixes_product_name_resolver_fallback(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
        self::assertStringContainsString(
            '$product->get_name()',
            $source
        );
        self::assertStringNotContainsString(
            "(int)\$product->get_id(),\n    \$displayname\n",
            $source
        );
    }

    public function test_content_hub_opens_content_focused_builder(): void {
        $root = dirname(__DIR__, 3);
        $hub = file_get_contents(
            $root . '/admin/commerce/products/storefront.php'
        );
        $builder = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            "'area' => 'content'",
            $hub
        );
        self::assertStringContainsString(
            "\$contentfocus = \$builderarea === 'content';",
            $builder
        );
        self::assertStringContainsString(
            'commerce-storefront-builder--n813-content',
            $builder
        );
    }

    public function test_builder_offers_graphical_block_chooser(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        foreach ([
            'commerce-storefront-n813-type-grid',
            'data-n813-section-type',
            'commerce_storefront_n813_type_hero_help',
            'commerce_storefront_n813_type_image_text_help',
            'commerce_storefront_n813_type_video_help',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_builder_has_graphical_layout_choices_and_live_preview(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        foreach ([
            'commerce-storefront-n813-layout-grid',
            'data-n813-control',
            'section_hero_layout_',
            'section_image_position_',
            'commerce-storefront-n813-live-preview',
            'commerce_storefront_n813_layout_choice_help',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_presentation_distribution_and_tools_use_dedicated_pages(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront.php'
        );

        self::assertStringContainsString(
            'storefront_presentation.php',
            $source
        );
        self::assertStringContainsString(
            'storefront_distribution.php',
            $source
        );
        self::assertStringContainsString(
            'storefront_tools.php',
            $source
        );
        self::assertStringNotContainsString(
            '#n812-presentation',
            $source
        );
        self::assertStringNotContainsString(
            '#n812-distribution',
            $source
        );
        self::assertStringNotContainsString(
            '#n812-tools',
            $source
        );
    }

    public function test_n813_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
