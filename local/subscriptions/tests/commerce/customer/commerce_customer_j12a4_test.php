<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_customer_j12a4_test extends advanced_testcase {
    public function test_owned_digital_hides_redundant_library_button(): void {
        $template = file_get_contents(
            dirname(__DIR__, 3)
                . '/templates/storefront/product_commerce_panel.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString('{{^ownedisdigital}}', $template);
        self::assertStringContainsString(
            'commerce-product-commerce-panel--owned-digital',
            $template
        );
        self::assertStringContainsString(
            'commerce-product-owned-downloads--compact',
            $template
        );
    }

    public function test_owned_digital_panel_is_visually_removed(): void {
        $css = file_get_contents(
            dirname(__DIR__, 3) . '/styles/storefront.css'
        );

        self::assertIsString($css);
        self::assertStringContainsString(
            '.commerce-product-commerce-panel--owned-digital',
            $css
        );
        self::assertStringContainsString('border: 0 !important;', $css);
        self::assertStringContainsString('padding: 0 !important;', $css);
        self::assertStringContainsString('margin-top: 0 !important;', $css);
    }
}
