<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n818_test extends advanced_testcase {
    public function test_builder_preview_is_collapsible_and_not_duplicated(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            'commerce-storefront-n818-preview',
            $source
        );
        self::assertStringContainsString(
            'data-region\' => \'storefront-block-preview',
            $source
        );
        self::assertStringContainsString(
            'if (!$contentfocus) {',
            $source
        );
    }

    public function test_structured_blocks_do_not_show_generic_content_editor(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            "in_array(\$row['type'], ['features', 'faq'], true)",
            $source
        );
        self::assertStringContainsString(
            'commerce_storefront_n818_feature_items',
            $source
        );
        self::assertStringContainsString(
            'commerce_storefront_n818_faq_items',
            $source
        );
    }

    public function test_status_service_accepts_builder_item_text(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/storefront/admin/CommerceStorefrontSectionStatusService.php'
        );

        self::assertStringContainsString(
            "\$hasitemtext = is_string(\$rawitems)",
            $source
        );
        self::assertStringContainsString(
            'missing_requirement',
            $source
        );
    }

    public function test_responsive_preview_targets_only_block_preview(): void {
        $root = dirname(__DIR__, 3);
        $js = file_get_contents(
            $root . '/amd/src/storefront_builder_drag_drop.js'
        );
        $css = file_get_contents(
            $root . '/styles/storefront_builder.css'
        );

        self::assertStringContainsString(
            'commerce-storefront-block-preview--mobile',
            $js
        );
        self::assertStringContainsString(
            '.commerce-storefront-block-preview--mobile',
            $css
        );
        self::assertStringNotContainsString(
            '.commerce-storefront-preview-canvas--mobile',
            $css
        );
    }

    public function test_n818_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
