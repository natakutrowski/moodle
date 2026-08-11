<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_customer_j12a3_test extends advanced_testcase {
    public function test_catalog_uses_available_font_awesome_filter_icon(): void {
        $template = file_get_contents(
            dirname(__DIR__, 3) . '/templates/storefront/catalog.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString('fa-solid fa-filter', $template);
        self::assertStringNotContainsString('bi bi-funnel', $template);
        self::assertStringContainsString(
            'commerce-storefront__filters-chevron',
            $template
        );
    }

    public function test_owned_digital_downloads_are_compact_and_typed(): void {
        $template = file_get_contents(
            dirname(__DIR__, 3)
                . '/templates/storefront/product_commerce_panel.mustache'
        );
        $controller = file_get_contents(
            dirname(__DIR__, 3) . '/storefront_product.php'
        );
        $css = file_get_contents(
            dirname(__DIR__, 3) . '/styles/storefront.css'
        );

        self::assertIsString($template);
        self::assertIsString($controller);
        self::assertIsString($css);
        self::assertStringContainsString(
            'commerce-product-owned-downloads--compact',
            $template
        );
        self::assertStringContainsString('{{buttonclass}}', $template);
        self::assertStringNotContainsString(
            'btn btn-outline-primary btn-lg w-100',
            $template
        );
        self::assertStringContainsString("'btn-primary'", $controller);
        self::assertStringContainsString("'btn-outline-primary'", $controller);
        self::assertStringContainsString('width: auto !important;', $css);
    }
}
