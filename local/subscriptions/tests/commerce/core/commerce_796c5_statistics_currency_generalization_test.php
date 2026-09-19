<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\crm\commerce\statistics\CommerceGlobalStatisticsFilterRenderer;
use moodle_url;

/** Tests 7.96C5 global statistics currency generalization. */
final class commerce_796c5_statistics_currency_generalization_test extends advanced_testcase {
    public function test_statistics_currency_filter_uses_registry_options(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,JPY,TND', 'local_subscriptions');

        $options = (new CommerceCurrencyRegistry())->options();
        $html = CommerceGlobalStatisticsFilterRenderer::render(
            new moodle_url('/local/subscriptions/admin/commerce/statistics/index.php'),
            '30',
            '',
            '',
            'JPY',
            '',
            new moodle_url('/local/subscriptions/admin/commerce/statistics/export.php'),
            $options
        );

        self::assertStringContainsString('value="JPY"', $html);
        self::assertStringContainsString('🇯🇵 JPY', $html);
        self::assertStringContainsString('🇹🇳 TND', $html);
        self::assertStringNotContainsString('🌐 JPY', $html);
    }

    public function test_global_statistics_surfaces_do_not_force_two_decimal_currency_math(): void {
        $root = dirname(__DIR__, 3);
        $files = [
            '/admin/commerce/statistics/index.php',
            '/admin/commerce/statistics/export.php',
            '/classes/commerce/statistics/CommerceGlobalStatisticsDashboardRepository.php',
        ];

        foreach ($files as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertIsString($source, $relative);
            self::assertStringNotContainsString("['','EUR','RUB']", $source, $relative);
        }

        $index = file_get_contents($root . $files[0]);
        self::assertStringContainsString('CurrencyFormatter::format_minor_code', $index);
        self::assertStringNotContainsString('$minor/100', $index);

        $export = file_get_contents($root . $files[1]);
        self::assertStringContainsString('CommerceCurrencyAmount::major_float_from_minor', $export);
        self::assertStringContainsString('Currency::minor_unit_exponent', $export);
        self::assertStringNotContainsString('/100,$money', $export);

        $repository = file_get_contents($root . $files[2]);
        self::assertStringContainsString('Currency::is_known($currency)', $repository);
        self::assertStringNotContainsString('in_array($currency,[\'EUR\',\'RUB\']', $repository);
    }
}
