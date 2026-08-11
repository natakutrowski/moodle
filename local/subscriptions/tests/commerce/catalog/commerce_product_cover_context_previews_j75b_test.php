<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_product_cover_context_previews_j75b_test
        extends \advanced_testcase {

    public function test_assets_page_loads_real_surface_stylesheets(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/assets.php'
        );

        $this->assertStringContainsString(
            '/local/subscriptions/styles/storefront.css',
            $source
        );
        $this->assertStringContainsString(
            '/local/subscriptions/styles/my_digital_products.css',
            $source
        );
        $this->assertStringContainsString(
            'product_cover_context_previews',
            $source
        );
    }

    public function test_preview_template_reuses_real_component_classes(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/admin/'
            . 'product_cover_context_previews.mustache'
        );

        foreach ([
            'commerce-product-card',
            'commerce-product-page',
            'commerce-product-commerce-panel',
            'commerce-checkout-item',
            'digital-library-resource',
            'digital-library-file',
        ] as $class) {
            $this->assertStringContainsString($class, $template);
        }
    }

    public function test_preview_resolves_all_four_cover_contexts(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/assets.php'
        );

        foreach ([
            'CommerceProductCoverContext::STOREFRONT',
            'CommerceProductCoverContext::PRODUCT',
            'CommerceProductCoverContext::CHECKOUT',
            'CommerceProductCoverContext::RESOURCES',
        ] as $context) {
            $this->assertStringContainsString($context, $source);
        }
    }
}
