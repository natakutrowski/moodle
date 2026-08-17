<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n81_test extends advanced_testcase {
    public function test_products_index_has_unified_kpis_filters_and_scope(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );

        foreach ([
            'CommerceDesignSystemRenderer::metrics([',
            'crm-sales-filter-panel crm-products-filter-panel',
            'crm-products-filter-grid',
            'crm-result-scope-pill',
            'commerce_products_found',
            'commerce_product_kpi_incomplete',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_products_index_supports_business_filters_and_sorting(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );

        foreach ([
            "optional_param('status'",
            "optional_param('validation'",
            "optional_param('sort'",
            "optional_param('dir'",
            "optional_param('perpage'",
            "'active', 'inactive', 'archived'",
            "'valid', 'incomplete'",
            "'name', 'type', 'status', 'price'",
            'crm-product-sort-link',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_products_table_hides_sku_from_primary_name_cell(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );

        self::assertStringContainsString(
            "'crm-product-origin'",
            $source
        );
        self::assertStringNotContainsString(
            "s(\$product->get_sku()) . ' (' . s(\$originlabel)",
            $source
        );
    }

    public function test_products_actions_use_display_button_and_context_menu(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );

        foreach ([
            'fa fa-eye me-1',
            'crm-product-action-outline',
            'crm-sales-row-actions-menu',
            'commerce_product_menu_product',
            'commerce_product_menu_commerce',
            'commerce_product_menu_sales',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_products_index_has_25_50_100_pagination(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );

        self::assertStringContainsString(
            "[25 => '25', 50 => '50', 100 => '100']",
            $source
        );
        self::assertStringContainsString(
            '$OUTPUT->paging_bar(',
            $source
        );
    }


    public function test_products_index_has_top_add_action_split_status_and_currency_flags(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            'crm-products-top-actions',
            $source
        );
        self::assertStringContainsString(
            "'class' => 'fa fa-plus me-1'",
            $source
        );
        self::assertStringContainsString(
            '$statusbadge',
            $source
        );
        self::assertStringContainsString(
            '$validationbadge',
            $source
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
            '.crm-products-table .badge',
            $styles
        );
    }

    public function test_n81_does_not_bump_plugin_version(): void {
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
