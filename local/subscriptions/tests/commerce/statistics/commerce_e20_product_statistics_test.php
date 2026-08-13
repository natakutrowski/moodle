<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Static certification of the E20 product statistics slice. */
final class commerce_e20_product_statistics_test extends \advanced_testcase {
    public function test_product_statistics_are_currency_safe_and_payment_aware(): void {
        $root = dirname(__DIR__, 3);
        $repository = file_get_contents($root . '/classes/commerce/statistics/CommerceStatisticsRepository.php');
        $page = file_get_contents($root . '/admin/commerce/statistics/index.php');
        $renderer = file_get_contents(
            $root . '/classes/crm/commerce/statistics/CommerceProductStatisticsRenderer.php'
        );

        self::assertIsString($repository);
        self::assertIsString($page);
        self::assertIsString($renderer);
        self::assertStringContainsString('function product_statistics(', $repository);
        self::assertStringContainsString(
            "pay.status IN ('paid', 'succeeded', 'completed', 'captured')",
            $repository
        );
        self::assertStringContainsString(
            'GROUP BY pi.itemreference, pi.label, pi.itemtype, pi.currency',
            $repository
        );
        self::assertStringContainsString(
            'CommerceGlobalStatisticsDashboardRepository',
            $page
        );
        self::assertStringContainsString(
            'CommerceGlobalStatisticsDashboardRenderer',
            $page
        );
        self::assertStringContainsString('self::sale_type($row->itemtype)', $renderer);
        self::assertStringContainsString("'subscription' => 'commerce_purchase_type_subscription'", $renderer);
        self::assertStringContainsString("'digital' => 'commerce_purchase_type_digital'", $renderer);
    }

    public function test_product_page_contains_metrics_charts_and_safe_html_translations(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/products/view.php');

        self::assertIsString($page);
        self::assertStringContainsString('CommerceProductStatisticsDashboardRepository', $page);
        self::assertStringContainsString('CommerceProductStatisticsDashboardRenderer', $page);
        self::assertStringContainsString("'fr' => '🇫🇷'", $page);
        self::assertStringContainsString("'en' => '🇬🇧'", $page);
        self::assertStringContainsString("'ru' => '🇷🇺'", $page);
        self::assertStringContainsString(
            "format_text(\$translation['shortdescription'], FORMAT_HTML)",
            $page
        );
        self::assertStringContainsString(
            "format_text(\$translation['description'], FORMAT_HTML)",
            $page
        );
    }
}
