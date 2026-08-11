<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_customer_j12a2_test extends advanced_testcase {
    public function test_catalog_keeps_custom_disclosure_indicator_and_filter_icon(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/catalog.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString(
            'commerce-storefront__filters-chevron',
            $template
        );
        self::assertStringContainsString(
            'fa-solid fa-filter',
            $template
        );
        self::assertStringNotContainsString('bi bi-funnel', $template);
    }

    public function test_single_owned_action_uses_unframed_compact_panel(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/'
                . 'product_commerce_panel.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/styles/storefront.css'
        );

        self::assertIsString($template);
        self::assertIsString($css);
        self::assertStringContainsString(
            'commerce-product-commerce-panel--owned-single',
            $template
        );
        self::assertStringContainsString(
            'commerce-product-commerce-panel__back--owned',
            $template
        );
        self::assertStringContainsString(
            '.commerce-product-commerce-panel--owned-single',
            $css
        );
        self::assertStringContainsString(
            'border: 0 !important;',
            $css
        );
    }
}
