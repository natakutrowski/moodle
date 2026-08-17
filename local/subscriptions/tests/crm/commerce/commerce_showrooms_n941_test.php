<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n941_test extends advanced_testcase {
    public function test_three_editor_pages_use_harmonised_titles_and_subtitles(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        foreach ([
            'commerce_showroom_n941_information_page_title',
            'commerce_showroom_n941_information_page_subtitle',
            'commerce_showroom_n941_seo_page_title',
            'commerce_showroom_n941_seo_page_subtitle',
            'commerce_showroom_n941_builder_page_title',
            'commerce_showroom_n941_builder_page_subtitle',
        ] as $key) {
            self::assertStringContainsString($key, $source);
        }
    }

    public function test_builder_block_count_is_not_rendered_twice(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringNotContainsString(
            "count(\$blocks),\n            'commerce-showroom-builder__count-number'",
            $source
        );
        self::assertStringContainsString(
            "'commerce_showroom_n94_block_count'",
            $source
        );
    }

    public function test_seo_does_not_repeat_page_intro_inside_form(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringNotContainsString(
            "'commerce_showroom_n932_seo_title'",
            $source
        );
    }

    public function test_history_has_no_redundant_back_to_showroom_button(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/history.php'
        );

        self::assertStringNotContainsString(
            'crm-showroom-history-top-actions',
            $source
        );
        self::assertStringNotContainsString(
            'commerce_showroom_n92_back_showroom',
            $source
        );
    }

    public function test_n941_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
