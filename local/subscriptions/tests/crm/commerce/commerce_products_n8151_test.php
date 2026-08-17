<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n8151_test extends advanced_testcase {
    public function test_presentation_uses_existing_global_zones_string(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_presentation.php'
        );

        self::assertStringContainsString(
            "'commerce_storefront_global_zones_title'",
            $source
        );
        self::assertStringNotContainsString(
            "'commerce_storefront_global_zones'",
            $source
        );
    }

    public function test_visible_storefront_strings_do_not_expose_dev_phase_names(): void {
        $root = dirname(__DIR__, 3);

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $root . '/lang/' . $lang . '/local_subscriptions.php'
            );

            foreach ([
                'commerce_storefront_n812_content_help',
                'commerce_storefront_n812_builder_next_title',
                'commerce_storefront_n812_builder_next_help',
                'commerce_storefront_n812_builder_transition_help',
                'commerce_storefront_n813_builder_focus_help',
                'commerce_storefront_n812_defaults_help',
            ] as $key) {
                self::assertMatchesRegularExpression(
                    "/\\\$string\\['" . preg_quote($key, '/') . "'\\]\\s*=\\s*'([^']*)';/",
                    $source
                );
            }

            $visiblelines = implode("\n", array_filter(
                preg_split('/\R/', $source) ?: [],
                static fn(string $line): bool =>
                    str_contains($line, 'commerce_storefront_n812_content_help')
                    || str_contains($line, 'commerce_storefront_n812_builder_next_')
                    || str_contains($line, 'commerce_storefront_n812_builder_transition_help')
                    || str_contains($line, 'commerce_storefront_n813_builder_focus_help')
                    || str_contains($line, 'commerce_storefront_n812_defaults_help')
            ));

            self::assertStringNotContainsString('N8.13', $visiblelines);
            self::assertStringNotContainsString('N8.14', $visiblelines);
            self::assertStringNotContainsString('N8.15', $visiblelines);
        }
    }

    public function test_n8151_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
