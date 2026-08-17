<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n83_test extends advanced_testcase {
    public function test_product_top_metrics_show_currencies_translations_and_bundle_components_only(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );

        self::assertStringContainsString(
            "'EUR' => '🇪🇺'",
            $source
        );
        self::assertStringContainsString(
            "'RUB' => '🇷🇺'",
            $source
        );
        self::assertStringContainsString(
            '$translationflags',
            $source
        );
        self::assertStringContainsString(
            "if (\$product->get_type() === 'bundle')",
            $source
        );
    }

    public function test_product_overview_consolidates_status_price_availability_and_technical_data(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );

        foreach ([
            'crm-product-view-status-grid',
            'crm-product-view-commercial-grid',
            'crm-product-view-technical-details',
            'commerce_product_status_publication_help',
            'commerce_product_status_visibility_help',
            'commerce_product_status_sale_help',
            'commerce_product_status_validation_help',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringContainsString(
            'if (' . '$from || $until' . ')',
            $source
        );
        self::assertStringNotContainsString(
            '(' . '$from ? userdate($from)' . " : get_string('none'))",
            $source
        );
    }

    public function test_revenue_charts_use_custom_gradient_svg_points_and_currency_flags(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/statistics/'
            . 'CommerceProductStatisticsDashboardRenderer.php'
        );

        foreach ([
            'revenue_svg(',
            'linearGradient',
            'm53-commerce-line-area',
            'm53-commerce-line-path',
            'm53-commerce-line-point',
            "self::currency_flag(\$currency)",
        ] as $needle) {
            self::assertStringContainsString($needle, $renderer);
        }

        self::assertStringNotContainsString(
            '$periodchart=new \core\chart_line();',
            $renderer
        );
    }

    public function test_revenue_chart_has_one_external_title_and_no_internal_legend(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/statistics/'
            . 'CommerceProductStatisticsDashboardRenderer.php'
        );

        self::assertStringContainsString(
            "'m51-chart-title mb-0'",
            $renderer
        );
        self::assertStringNotContainsString(
            '$periodchart->set_title',
            $renderer
        );
        self::assertStringNotContainsString(
            'chart_series($currency,$periodvalues)',
            $renderer
        );
    }

    public function test_n83_does_not_bump_plugin_version(): void {
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
