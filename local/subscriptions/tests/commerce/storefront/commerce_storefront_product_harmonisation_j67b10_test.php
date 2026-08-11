<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B10 Storefront pricing harmonisation contract. */
final class commerce_storefront_product_harmonisation_j67b10_test
        extends \advanced_testcase {

    public function test_product_panel_reuses_catalogue_price_partial(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_commerce_panel.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            '{{> local_subscriptions/storefront/product_price }}',
            $template
        );
        $this->assertStringNotContainsString(
            'commerce-product-page__trial-offer',
            $template
        );
        $this->assertStringNotContainsString(
            'commerce-product-card__price-block',
            $template
        );
    }

    public function test_product_page_exposes_shared_price_labels(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/storefront_product.php'
        );

        $this->assertIsString($source);
        foreach ([
            'commerce_storefront_price_standard',
            'commerce_storefront_price_promotional',
            'commerce_storefront_price_trial',
            'commerce_storefront_price_upgrade',
            'commercialdiscountpercentage',
        ] as $contract) {
            $this->assertStringContainsString($contract, $source);
        }
    }

    public function test_shared_partial_covers_all_four_price_modes(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        foreach ([
            'commerce-storefront-price--standard',
            'commerce-storefront-price--promotion',
            'commerce-storefront-price--trial',
            'commerce-storefront-price--upgrade',
        ] as $class) {
            $this->assertStringContainsString($class, $template);
        }
    }
}
