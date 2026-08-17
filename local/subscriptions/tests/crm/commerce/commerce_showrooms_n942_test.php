<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n942_test extends advanced_testcase {
    public function test_builder_critical_layout_is_in_main_stylesheet(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            '.commerce-showroom-builder__toolbar-primary',
            $css
        );
        self::assertStringContainsString(
            'grid-template-columns:',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-block__more-panel',
            $css
        );
    }

    public function test_context_menus_are_positioned_against_viewport(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            "panel.style.position = 'fixed';",
            $source
        );
        self::assertStringContainsString(
            'summary.getBoundingClientRect()',
            $source
        );
        self::assertStringContainsString(
            'window.innerHeight',
            $source
        );
        self::assertStringContainsString(
            'window.innerWidth',
            $source
        );
    }

    public function test_n942_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
