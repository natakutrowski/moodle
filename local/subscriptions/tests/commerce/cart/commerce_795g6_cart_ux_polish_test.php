<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\cart;

defined('MOODLE_INTERNAL') || die();

final class commerce_795g6_cart_ux_polish_test extends \advanced_testcase {
    public function test_cart_action_uses_current_operation_result_api(): void {
        $source = file_get_contents(__DIR__ . '/../../../cart_action.php');
        self::assertIsString($source);
        self::assertStringContainsString('$result->has_changed()', $source);
        self::assertStringNotContainsString('$result->is_changed()', $source);
    }

    public function test_cart_template_hides_technical_sku_and_exposes_product_visuals(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache');
        $this->assertIsString($template);
        $this->assertStringContainsString('commerce-cart-line__visual--portrait', $template);
        $this->assertStringContainsString('fa-solid fa-trash-can', $template);
        $this->assertStringContainsString('{{> local_subscriptions/cart/price }}', $template);
        $this->assertStringNotContainsString('{{productsku}}', strip_tags($template));
    }

    public function test_summary_uses_ttc_total_without_tax_row(): void {
        $source = file_get_contents(__DIR__ . '/../../../templates/cart/summary.mustache');
        self::assertIsString($source);
        self::assertStringNotContainsString('{{taxlabel}}', $source);
        self::assertStringNotContainsString('{{taxformatted}}', $source);
        self::assertStringContainsString('commerce/payment_reassurance', $source);
    }

    public function test_payment_reassurance_prioritises_immediate_access_and_keeps_labels_compact(): void {
        $source = file_get_contents(__DIR__ . '/../../../templates/commerce/payment_reassurance.mustache');
        self::assertIsString($source);

        $accessposition = strpos($source, '{{instantaccesslabel}}');
        $secureposition = strpos($source, '{{paymentsecurelabel}}');
        self::assertNotFalse($accessposition);
        self::assertNotFalse($secureposition);
        self::assertLessThan($secureposition, $accessposition);

        $css = file_get_contents(__DIR__ . '/../../../styles/storefront.css');
        self::assertIsString($css);
        self::assertStringContainsString('white-space: nowrap;', $css);
    }

    public function test_g6_strings_exist_in_all_supported_languages(): void {
        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(__DIR__ . '/../../../lang/' . $language . '/local_subscriptions.php');
            self::assertIsString($source);
            self::assertStringContainsString("commerce_cart_total_ttc", $source);
            self::assertStringContainsString("commerce_cart_view_product", $source);
            self::assertStringContainsString("commerce_cart_payment_secure", $source);
            self::assertStringContainsString("commerce_cart_instant_access", $source);
        }
    }
}
