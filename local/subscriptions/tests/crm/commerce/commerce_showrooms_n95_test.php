<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n95_test extends advanced_testcase {
    public function test_context_menu_width_is_bounded(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            'width: 270px !important;',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-block__more-panel',
            $css
        );
    }

    public function test_block_dialog_uses_wide_viewport_layout(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents(
            $root . '/styles/showroom_builder.css'
        );

        self::assertStringContainsString(
            'width: min(92rem, calc(100vw - 3rem));',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-dialog__presentation',
            $css
        );
    }

    public function test_common_layout_fields_are_grouped_in_builder_runtime(): void {
        $root = dirname(__DIR__, 3);
        $js = file_get_contents(
            $root . '/js/showroom_builder.js'
        );

        foreach ([
            'sectionwidth',
            'sectionbackground',
            'sectionbackgroundimageurl',
            'sectionspacing',
            'sectionanimation',
        ] as $field) {
            self::assertStringContainsString(
                "'" . $field . "'",
                $js
            );
        }

        self::assertStringContainsString(
            'commerce-showroom-dialog__presentation',
            $js
        );
    }

    public function test_expanded_rows_expose_presentation_summary(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );
        $css = file_get_contents(
            $root . '/styles/showroom_builder.css'
        );

        self::assertStringContainsString(
            'commerce-showroom-block__expanded-info',
            $source
        );
        self::assertStringContainsString(
            '.commerce-showroom-block.is-collapsed',
            $css
        );
        self::assertStringContainsString(
            'commerce-showroom-block__expanded-chip',
            $source
        );
    }

    public function test_n95_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
