<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_currency_selector_l43b_test extends \advanced_testcase {
    public function test_boutique_and_builder_cta_share_the_same_currency_selector(): void {
        global $CFG;

        $partial = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/currency_selector.mustache'
        );
        $catalog = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/catalog.mustache'
        );
        $section = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/product_section.mustache'
        );
        $productpage = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/storefront_product.php'
        );

        $this->assertIsString($partial);
        $this->assertStringContainsString(
            'commerce-storefront__currency-icon',
            $partial
        );
        $this->assertStringContainsString(
            'commerce-storefront__currency-label',
            $partial
        );
        $this->assertStringContainsString(
            '{{#currencies}}',
            $partial
        );

        $partialcall = '{{> local_subscriptions/storefront/currency_selector }}';
        $this->assertStringContainsString($partialcall, $catalog);
        $this->assertStringContainsString($partialcall, $section);

        $this->assertStringContainsString(
            'local_subscriptions_storefront_currency',
            $productpage
        );
        $this->assertStringContainsString(
            'CommerceCurrencyLabelFormatter::format',
            $productpage
        );
        $this->assertStringNotContainsString(
            'currencyiseur',
            $productpage
        );
        $this->assertStringNotContainsString(
            'currencyisrub',
            $productpage
        );
    }
}
