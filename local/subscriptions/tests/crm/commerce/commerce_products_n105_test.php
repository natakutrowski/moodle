<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_products_n105_test extends advanced_testcase {
    public function test_course_commerce_journey_moved_to_products(): void {
        $root = dirname(__DIR__, 3);
        $products = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );
        $configuration = file_get_contents(
            $root . '/admin/commerce/configuration/index.php'
        );

        $this->assertStringContainsString(
            'commerce_product_course_journey_title',
            $products
        );
        $this->assertStringContainsString(
            'subscription_config::commerce_access_scopes_page()',
            $products
        );
        $this->assertStringContainsString(
            'subscription_config::commerce_plans_page()',
            $products
        );
        $this->assertStringNotContainsString(
            'commerce-config-catalogue',
            $configuration
        );
    }

    public function test_configuration_summary_badges_do_not_stretch(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles.css');

        $this->assertStringContainsString(
            'justify-self: end;',
            $css
        );
        $this->assertStringContainsString(
            'width: max-content;',
            $css
        );
    }

    public function test_engine_controls_from_n1038_are_preserved(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/configuration/section.php'
        );

        foreach ([
            'commerce_checkout_enabled',
            'commerce_fulfillment_enabled',
            'commerce_runtime_read_mode',
            'commerce_runtime_read_strict',
        ] as $key) {
            $this->assertStringContainsString(
                "\$field('" . $key . "'",
                $source
            );
        }

        $this->assertStringNotContainsString(
            "\$field('commerce_fulfillment_shadow_enabled'",
            $source
        );
        $this->assertStringContainsString(
            '$submittedrepair && !$submittedreconciliation',
            $source
        );
    }

    public function test_course_commerce_journey_is_collapsible_and_keeps_single_add_product_cta(): void {
        $root = dirname(__DIR__, 3);
        $products = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );

        $this->assertStringContainsString("'details'", $products);
        $this->assertStringContainsString(
            'crm-products-course-journey__summary',
            $products
        );
        $this->assertStringNotContainsString(
            "'open' =>",
            substr(
                $products,
                strpos($products, 'crm-products-course-journey__summary'),
                strpos($products, 'crm-products-top-actions')
                    - strpos($products, 'crm-products-course-journey__summary')
            )
        );
        $this->assertSame(
            1,
            substr_count($products, "'commerce_product_add'")
        );
    }

    public function test_engine_summary_switches_stack_vertically(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/configuration/index.php'
        );
        $css = file_get_contents($root . '/styles.css');

        $this->assertStringContainsString(
            'commerce-config-summary-stack',
            $source
        );
        $this->assertStringContainsString(
            '.commerce-config-summary-stack',
            $css
        );
        $this->assertStringContainsString(
            'flex-direction: column;',
            $css
        );
    }

    public function test_n105_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        $this->assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
