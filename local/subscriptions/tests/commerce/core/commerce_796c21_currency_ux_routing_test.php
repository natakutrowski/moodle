<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\currency\CommerceCurrencyLabelFormatter;
use local_subscriptions\currency\Currency;

final class commerce_796c21_currency_ux_routing_test extends advanced_testcase {
    public function test_currency_labels_use_central_visual_metadata_and_symbols(): void {
        $this->assertSame('🇯🇵 JPY (¥)', CommerceCurrencyLabelFormatter::format('JPY'));
        $this->assertSame('🇹🇳 TND (د.ت)', CommerceCurrencyLabelFormatter::format('TND'));
        $this->assertSame('🇯🇵', Currency::visual_marker('JPY'));
        $this->assertSame('🇹🇳', Currency::visual_marker('TND'));
    }

    public function test_native_cart_page_uses_generic_currency_selection_without_eur_rub_gate(): void {
        $source = file_get_contents(__DIR__ . '/../../../cart.php');
        self::assertIsString($source);

        self::assertStringContainsString(
            'CommerceShowroomCurrencyResolver::active_currencies(',
            $source
        );
        self::assertStringContainsString(
            'CommerceCurrencySurfaceSelectionService',
            $source
        );
        self::assertStringContainsString(
            '->resolve(',
            $source
        );
        self::assertStringNotContainsString(
            "in_array(\$currency, ['EUR', 'RUB']",
            $source
        );
    }

    public function test_product_view_uses_central_currency_visual_marker(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/view.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('Currency::visual_marker($pricecurrency)', $source);
        $this->assertStringNotContainsString("'EUR' => '🇪🇺'", $source);
    }
}
