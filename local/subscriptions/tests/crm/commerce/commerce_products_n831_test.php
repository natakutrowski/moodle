<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n831_test extends advanced_testcase {
    public function test_digital_product_view_does_not_repeat_cover_image(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );

        self::assertStringNotContainsString(
            "html_writer::tag('h3', get_string('commerce_cover_image'",
            $source
        );
    }

    public function test_digital_files_show_desktop_mobile_and_download_count(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );

        foreach ([
            "'fa-desktop'",
            "'fa-mobile'",
            'crm-product-view-digital-files',
            'crm-product-view-download-stat',
            'SUM(downloadcount)',
            'commerce_product_downloads_total',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_statistics_cards_are_neutral_but_keep_top_accent(): void {
        $root = dirname(__DIR__, 3);
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            '.m51-chart-card {',
            $styles
        );
        self::assertStringContainsString(
            'background: #fff !important;',
            $styles
        );
        self::assertStringContainsString(
            '.m51-chart-card::before',
            $styles
        );
        self::assertStringContainsString(
            '#f51b7b 0%',
            $styles
        );
    }

    public function test_n831_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        preg_match(
            '/\\$plugin->version\\s*=\\s*(\\d+);/',
            $version,
            $matches
        );
        self::assertGreaterThanOrEqual(
            2026081601,
            (int)($matches[1] ?? 0)
        );
    }
}
