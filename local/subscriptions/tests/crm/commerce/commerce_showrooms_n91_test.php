<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n91_test extends advanced_testcase {
    public function test_showroom_kpis_share_one_grid_and_published_is_emphasised(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/showrooms/index.php');
        $css = file_get_contents($root . '/styles.css');

        self::assertStringContainsString('crm-showrooms-kpis', $page);
        self::assertStringContainsString(
            'grid-template-columns: repeat(5, minmax(0, 1fr))',
            $css
        );
        self::assertStringContainsString(
            '.crm-commerce-metric:nth-child(2)',
            $css
        );
    }

    public function test_showroom_menu_is_fixed_to_avoid_page_scrollbar(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/showrooms/index.php');
        $css = file_get_contents($root . '/styles.css');

        self::assertStringContainsString("panel.style.position = 'fixed'", $page);
        self::assertStringContainsString(
            '.crm-showroom-row-actions .crm-sales-row-menu',
            $css
        );
        self::assertStringContainsString('position: fixed !important;', $css);
    }

    public function test_import_page_uses_commerce_navigation_and_progressive_json_disclosure(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/import.php'
        );

        self::assertStringContainsString(
            'CommerceSectionNavigationRenderer::SHOWROOMS',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n91_import_package_title',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n91_import_json_advanced',
            $source
        );
        self::assertStringContainsString(
            "'class' => 'crm-showroom-import-json card'",
            $source
        );
    }

    public function test_crm_shell_call_keeps_navigation_contract(): void {
        $root = dirname(__DIR__, 3);
        foreach (['index.php', 'import.php'] as $file) {
            $source = file_get_contents(
                $root . '/admin/commerce/showrooms/' . $file
            );
            self::assertStringContainsString(
                'CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS',
                $source
            );
        }
    }

    public function test_n91_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
