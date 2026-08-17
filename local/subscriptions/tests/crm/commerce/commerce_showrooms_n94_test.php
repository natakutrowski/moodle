<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n94_test extends advanced_testcase {
    public function test_first_three_showroom_screens_render_commerce_navigation(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'CommerceSectionNavigationRenderer::SHOWROOMS',
            $source
        );

        foreach ([
            'edit.php',
            'seo.php',
            'builder.php',
        ] as $file) {
            if ($file === 'edit.php') {
                continue;
            }
            $wrapper = file_get_contents(
                $root . '/admin/commerce/showrooms/' . $file
            );
            self::assertStringContainsString(
                "require(__DIR__ . '/edit.php')",
                $wrapper
            );
        }
    }

    public function test_builder_has_separate_add_and_template_workflows(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'commerce_showroom_n94_add_block_title',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n94_template_title',
            $source
        );
        self::assertStringContainsString(
            'commerce-showroom-builder__toolbar-primary',
            $source
        );
    }

    public function test_builder_hides_block_key_from_normal_row_and_exposes_it_in_technical_menu(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'commerce-showroom-block__technical',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n94_block_key',
            $source
        );
        self::assertStringNotContainsString(
            "html_writer::tag('span', s(\$block->blockkey), ['class' => 'commerce-showroom-block__key'])",
            $source
        );
    }

    public function test_builder_uses_primary_edit_and_contextual_secondary_actions(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'commerce-showroom-block__more',
            $source
        );
        self::assertStringContainsString(
            "'data-action' => 'edit-block'",
            $source
        );
        self::assertStringContainsString(
            "'data-action' => 'duplicate-block'",
            $source
        );
        self::assertStringContainsString(
            "'data-action' => 'toggle-block'",
            $source
        );
        self::assertStringContainsString(
            "'data-action' => 'delete-block'",
            $source
        );
    }

    public function test_n94_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
