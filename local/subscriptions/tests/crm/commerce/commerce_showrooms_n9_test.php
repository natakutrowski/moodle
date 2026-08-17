<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n9_test extends advanced_testcase {
    public function test_showroom_index_uses_shared_commerce_navigation_and_metrics(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/index.php'
        );

        self::assertStringContainsString(
            'CommerceSectionNavigationRenderer::SHOWROOMS',
            $source
        );
        self::assertStringContainsString(
            'CommerceDesignSystemRenderer::metrics([',
            $source
        );
    }

    public function test_showroom_index_has_collapsible_filters_sorting_and_paging(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/index.php'
        );

        self::assertStringContainsString(
            'crm-showrooms-filter-panel',
            $source
        );
        self::assertStringContainsString(
            '$sortlink = static function(',
            $source
        );
        self::assertStringContainsString(
            '$OUTPUT->paging_bar(',
            $source
        );
    }

    public function test_showroom_table_hides_technical_key_and_uses_contextual_actions(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/index.php'
        );

        self::assertStringNotContainsString(
            "get_string('commerce_showroom_cms_key'",
            $source
        );
        self::assertStringContainsString(
            'crm-showroom-primary-action',
            $source
        );
        self::assertStringContainsString(
            'crm-showroom-row-actions',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n9_menu_public',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n9_menu_admin',
            $source
        );
    }

    public function test_showroom_public_urls_are_language_aware_and_clickable(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/index.php'
        );

        self::assertStringContainsString(
            "'fr' => '🇫🇷'",
            $source
        );
        self::assertStringContainsString(
            "'en' => '🇬🇧'",
            $source
        );
        self::assertStringContainsString(
            "'ru' => '🇷🇺'",
            $source
        );
        self::assertStringContainsString(
            'crm-showroom-public-link',
            $source
        );
    }

    public function test_n9_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
