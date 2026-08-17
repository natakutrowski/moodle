<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n8191_test extends advanced_testcase {
    public function test_preview_buttons_use_dedicated_device_attribute(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            "'data-block-preview-device' => \$device",
            $page
        );
        self::assertStringContainsString(
            "'data-block-preview-mode' => 'desktop'",
            $page
        );
        self::assertStringNotContainsString(
            "'data-preview-device' => \$device",
            $page
        );
    }

    public function test_builder_contains_direct_responsive_preview_fallback(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/products/storefront_builder.php'
        );

        self::assertStringContainsString(
            "closest('[data-region=\"storefront-section-card\"]')",
            $page
        );
        self::assertStringContainsString(
            "commerce-storefront-block-preview--' + device",
            $page
        );
    }

    public function test_responsive_css_has_explicit_tablet_and_mobile_widths(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents(
            $root . '/styles/storefront_builder.css'
        );

        self::assertStringContainsString(
            'width: 768px !important;',
            $css
        );
        self::assertStringContainsString(
            'width: 390px !important;',
            $css
        );
        self::assertStringContainsString(
            'column-gap: .9rem !important;',
            $css
        );
    }

    public function test_n8191_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
