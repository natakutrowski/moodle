<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n92_test extends advanced_testcase {
    public function test_index_preserves_legacy_showroom_breadcrumb_contract(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/index.php'
        );

        self::assertStringContainsString(
            "get_string('commerce_showroom_cms_title', 'local_subscriptions')",
            $source
        );
    }

    public function test_history_uses_commerce_navigation_and_compact_summary(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/history.php'
        );

        self::assertStringContainsString(
            'CommerceSectionNavigationRenderer::SHOWROOMS',
            $source
        );
        self::assertStringContainsString(
            'crm-showroom-history-summary',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n92_history_title',
            $source
        );
    }

    public function test_history_translates_workflow_actions_and_paginates(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/history.php'
        );

        self::assertStringContainsString(
            "'publish' => get_string(",
            $source
        );
        self::assertStringContainsString(
            "'submit_review' => get_string(",
            $source
        );
        self::assertStringContainsString(
            "'return_draft' => get_string(",
            $source
        );
        self::assertStringContainsString(
            '$OUTPUT->paging_bar(',
            $source
        );
    }

    public function test_history_restore_has_confirmation_and_latest_revision_focus(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/history.php'
        );

        self::assertStringContainsString(
            'commerce_showroom_n92_restore_confirm',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n92_latest_badge',
            $source
        );
    }

    public function test_n92_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
