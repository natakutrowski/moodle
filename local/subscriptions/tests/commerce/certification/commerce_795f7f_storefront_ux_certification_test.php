<?php

namespace local_subscriptions;

use advanced_testcase;

/** Current Storefront source certification. */
final class commerce_795f7f_storefront_ux_certification_test
        extends advanced_testcase {

    public function test_current_storefront_sources_are_certifiable(): void {
        $root = dirname(__DIR__, 3);
        $required = [
            'templates/storefront/product_card.mustache',
            'templates/storefront/product_price.mustache',
            'templates/storefront/product_scaffold.mustache',
            'templates/storefront/product_commerce_panel.mustache',
            'templates/storefront/product_templates/default.mustache',
            'styles/storefront.css',
        ];

        foreach ($required as $relative) {
            $this->assertFileExists($root . '/' . $relative);
        }

        $card = file_get_contents(
            $root . '/templates/storefront/product_card.mustache'
        );
        $price = file_get_contents(
            $root . '/templates/storefront/product_price.mustache'
        );
        $panel = file_get_contents(
            $root
            . '/templates/storefront/product_commerce_panel.mustache'
        );

        $this->assertStringContainsString(
            'commerce-product-type-badge',
            $card
        );
        $this->assertStringContainsString(
            'local_subscriptions/storefront/product_badges',
            $card
        );
        $this->assertStringContainsString(
            'commerce-storefront-price',
            $price
        );
        $this->assertStringContainsString(
            'local_subscriptions/storefront/product_price',
            $panel
        );
    }
}
