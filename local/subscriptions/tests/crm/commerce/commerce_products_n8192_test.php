<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n8192_test extends advanced_testcase {
    public function test_presentation_defines_language_before_builder_tab(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_presentation.php'
        );

        self::assertIsString($source);
        $definition = strpos(
            $source,
            '$editlanguage = optional_param('
        );
        $builderurl = strpos(
            $source,
            "'editlang' => \$editlanguage"
        );

        self::assertNotFalse($definition);
        self::assertNotFalse($builderurl);
        self::assertLessThan($builderurl, $definition);
    }

    public function test_presentation_preserves_language_across_storefront_tabs(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront_presentation.php'
        );

        self::assertGreaterThanOrEqual(
            3,
            substr_count($source, "'editlang' => \$editlanguage")
        );
    }

    public function test_n8192_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
