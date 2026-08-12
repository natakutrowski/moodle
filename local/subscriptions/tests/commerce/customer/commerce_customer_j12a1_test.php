<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_j12a1_test extends \advanced_testcase {
    public function test_storefront_filters_and_conditional_back_link(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions';
        $catalog = file_get_contents(
            $root . '/templates/storefront/catalog.mustache'
        );
        $panel = file_get_contents(
            $root . '/templates/storefront/product_commerce_panel.mustache'
        );
        $page = file_get_contents($root . '/storefront_product.php');

        self::assertIsString($catalog);
        self::assertIsString($panel);
        self::assertIsString($page);
        self::assertStringContainsString(
            'data-storefront-filters open',
            $catalog
        );
        self::assertStringContainsString(
            'commerce-storefront__filters-chevron',
            $catalog
        );
        self::assertStringContainsString('{{#showbacklink}}', $panel);
        self::assertStringContainsString(
            "\$data['showbacktoshop']",
            $page
        );
        self::assertStringContainsString(
            'commerce-product-owned-primary',
            $panel
        );
    }
}
