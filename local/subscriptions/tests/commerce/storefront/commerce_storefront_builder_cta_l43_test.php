<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_builder_cta_l43_test extends \advanced_testcase {
    public function test_builder_cta_reuses_native_commercial_pricing_and_currency(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/storefront/product_section.mustache');
        $page = file_get_contents($root . 'storefront_product.php');
        $price = file_get_contents($root . 'templates/storefront/product_price.mustache');
        $currencyselector = file_get_contents(
            $root . 'templates/storefront/currency_selector.mustache'
        );

        self::assertIsString($template);
        self::assertIsString($page);
        self::assertIsString($price);
        self::assertIsString($currencyselector);

        self::assertStringContainsString(
            '{{> local_subscriptions/storefront/product_price }}',
            $template
        );
        self::assertStringContainsString('{{#haspromotion}}', $price);
        self::assertStringContainsString('{{compareformatted}}', $price);
        self::assertStringContainsString('{{discountlabel}}', $price);

        self::assertStringContainsString('commerce-product-cta__currency', $template);

        // Currency selection is now data-driven instead of hard-coded EUR/RUB flags.
        self::assertStringContainsString('$availablecurrencies', $page);
        self::assertStringContainsString("\$data['currencies'] = array_map(", $page);
        self::assertStringContainsString("'selected' => \$currency === \$code", $page);
        self::assertStringContainsString('{{#currencies}}', $currencyselector);
        self::assertStringContainsString('name="currency"', $currencyselector);

        self::assertStringNotContainsString('currencyiseur', $page);
        self::assertStringNotContainsString('currencyisrub', $page);

        self::assertStringContainsString('upgradetoggleaction', $template);
    }
}
