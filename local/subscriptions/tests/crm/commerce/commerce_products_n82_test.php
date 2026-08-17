<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n82_test extends advanced_testcase {
    public function test_product_view_hides_sku_from_header_and_moves_it_to_technical_details(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );

        self::assertStringContainsString(
            'commerce_product_technical_information',
            $source
        );
        self::assertStringContainsString(
            'commerce_product_technical_sku',
            $source
        );
        self::assertStringContainsString(
            'crm-product-view-technical-details',
            $source
        );
        self::assertStringNotContainsString(
            "html_writer::tag('code', s(\$product->get_sku()), ['class' => 'd-inline-block ms-2'])",
            $source
        );
    }

    public function test_product_view_actions_have_suggestive_icons(): void {
        $root = dirname(__DIR__, 3);
        $view = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );
        $renderer = file_get_contents(
            $root . '/classes/crm/commerce/presentation/CommerceDesignSystemRenderer.php'
        );

        self::assertStringContainsString("'icon' => 'fa-arrow-left'", $view);
        self::assertStringContainsString("'icon' => 'fa-pencil'", $view);
        self::assertStringContainsString("'icon' => 'fa-picture-o'", $view);
        self::assertStringContainsString("!empty(\$action['icon'])", $renderer);
    }

    public function test_product_statistics_toolbar_has_compact_right_aligned_actions(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            'm51-stat-toolbar-actions',
            $source
        );
        self::assertStringContainsString(
            'fa fa-file-excel-o me-1',
            $source
        );
        self::assertStringContainsString(
            'btn crm-product-action-primary btn-sm',
            $source
        );
        self::assertStringContainsString(
            '.m51-stat-toolbar-actions',
            $styles
        );
    }

    public function test_product_charts_use_commerce_pink_palette_and_gradient_cards(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/statistics/'
            . 'CommerceProductStatisticsDashboardRenderer.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            'm53-commerce-line-path',
            $renderer
        );
        self::assertStringContainsString(
            'stop-color="#f51b7b"',
            $renderer
        );
        self::assertStringContainsString(
            "'#8b5cf6'",
            $renderer
        );
        self::assertStringContainsString(
            'linear-gradient(',
            $styles
        );
        self::assertStringContainsString(
            '#f51b7b 0%',
            $styles
        );
    }

    public function test_n82_does_not_bump_plugin_version(): void {
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
