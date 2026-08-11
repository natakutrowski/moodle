<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B9 Boutique filtering and pricing harmonisation. */
final class commerce_storefront_harmonisation_j67b9_test
        extends \advanced_testcase {

    public function test_owned_filter_is_available_and_defaulted_server_side(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/digital_catalog.php'
        );
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/catalog.mustache'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'hideowned'",
            $source
        );
        $this->assertStringContainsString(
            '$customerid > 0 ? 1 : 0',
            $source
        );
        $this->assertStringContainsString(
            "empty(\$card['owned'])",
            $source
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            'storefront-hide-owned',
            $template
        );
        $this->assertStringContainsString(
            'name="hideowned"',
            $template
        );
    }

    public function test_upgrade_badge_and_path_share_one_line(): void {
        global $CFG;
        $price = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $this->assertStringContainsString('commerce-storefront-price__badges--upgrade', $price);
        $this->assertStringContainsString('commerce-storefront-price__heading--upgrade', $price);
        $this->assertStringContainsString('{{upgradefromlabel}}', $price);
        $this->assertStringContainsString('{{upgradetolabel}}', $price);
    }

    public function test_all_price_cases_use_shared_component_classes(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        foreach ([
            'commerce-storefront-price--upgrade',
            'commerce-storefront-price--trial',
            'commerce-storefront-price--promotion',
            'commerce-storefront-price--standard',
            'commerce-storefront-price__badge',
            'commerce-storefront-price__values',
        ] as $contract) {
            $this->assertStringContainsString(
                $contract,
                $template
            );
        }
    }

    public function test_featured_product_layout_is_untouched(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_card.mustache'
        );

        $this->assertStringContainsString(
            '{{#featured}}col-12{{/featured}}',
            $template
        );
        $this->assertStringContainsString(
            '{{^featured}}col-12 col-lg-6{{/featured}}',
            $template
        );
    }
}
