<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\currency\CurrencyFormatter;

/** Tests 7.96C6 native statistics and purchases currency consumers. */
final class commerce_796c6_statistics_purchases_currency_consumers_test extends advanced_testcase {
    public function test_currency_formatter_preserves_zero_two_and_three_minor_units(): void {
        self::assertStringContainsString('449', CurrencyFormatter::format_minor_code(449, 'JPY'));
        self::assertStringContainsString('14', CurrencyFormatter::format_minor_code(14925, 'TND'));
        self::assertStringContainsString('925', CurrencyFormatter::format_minor_code(14925, 'TND'));
        self::assertStringContainsString('30', CurrencyFormatter::format_minor_code(3000, 'EUR'));
    }

    public function test_native_statistics_and_purchase_surfaces_use_currency_core(): void {
        $root = dirname(__DIR__, 3);
        $checks = [
            '/admin/commerce/purchases/index.php' => [
                'CommerceCurrencyRegistry',
                'CurrencyFormatter::format_minor_code',
            ],
            '/admin/commerce/products/view.php' => [
                'CurrencyFormatter::format_minor_code',
            ],
            '/admin/commerce/products/statistics_export.php' => [
                'CommerceCurrencyAmount::major_float_from_minor',
                'Currency::minor_unit_exponent',
            ],
            '/classes/crm/commerce/statistics/CommerceProductStatisticsRenderer.php' => [
                'CurrencyFormatter::format_minor_code',
            ],
            '/classes/crm/commerce/statistics/CommerceStatisticsPageRenderer.php' => [
                'CurrencyFormatter::format_minor_code',
            ],
            '/classes/crm/commerce/statistics/CommerceStatisticsChartRenderer.php' => [
                'CommerceCurrencyAmount::major_float_from_minor',
                'Currency::minor_unit_exponent',
            ],
            '/classes/crm/commerce/statistics/CommerceProductStatisticsDashboardRenderer.php' => [
                'CommerceCurrencyAmount::major_float_from_minor',
            ],
            '/classes/crm/commerce/statistics/CommerceGlobalStatisticsDashboardRenderer.php' => [
                'CommerceCurrencyAmount::major_float_from_minor',
                'CurrencyFormatter::format_minor_code',
            ],
        ];

        foreach ($checks as $relative => $needles) {
            $source = file_get_contents($root . $relative);
            self::assertIsString($source, $relative);
            foreach ($needles as $needle) {
                self::assertStringContainsString($needle, $source, $relative);
            }
        }

        $purchases = file_get_contents($root . '/admin/commerce/purchases/index.php');
        self::assertStringNotContainsString("'EUR' => 'EUR', 'RUB' => 'RUB', 'USD' => 'USD'", $purchases);

        $productrenderer = file_get_contents(
            $root . '/classes/crm/commerce/statistics/CommerceProductStatisticsRenderer.php'
        );
        self::assertStringNotContainsString('$minor / 100', $productrenderer);
    }
}
