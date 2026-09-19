<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n121_inbox_workspace_refresh_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_inbox_workspace_exposes_manual_refresh_for_inbox_managers(): void {
        $page = $this->file('admin/inbox/index.php');
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            'InboxWorkspaceRenderer::render(',
            $page
        );
        self::assertStringContainsString(
            'Capabilities::MANAGE_INBOX',
            $renderer
        );
        self::assertStringContainsString(
            'admin_inbox_sync_page()',
            $renderer
        );
        self::assertStringContainsString(
            "'crm_inbox_refresh'",
            $renderer
        );
        self::assertStringContainsString(
            "'name' => 'sesskey'",
            $renderer
        );
    }

    public function test_manual_refresh_endpoint_is_post_and_sesskey_protected(): void {
        $page = $this->file('admin/inbox/sync.php');

        self::assertStringContainsString(
            'Capabilities::MANAGE_INBOX',
            $page
        );
        self::assertStringContainsString(
            'require_sesskey();',
            $page
        );
        self::assertStringContainsString(
            'InboxManualSyncService',
            $page
        );
        self::assertStringContainsString(
            'sync_enabled_accounts()',
            $page
        );
    }

    public function test_inbox_sync_route_is_registered_in_subscription_config(): void {
        $config = $this->file('classes/subscription_config.php');

        self::assertStringContainsString(
            'admin_inbox_sync_page',
            $config
        );
        self::assertStringContainsString(
            "'admin/inbox/sync.php'",
            $config
        );
    }

    public function test_plugin_version_is_unchanged(): void {
        $version = $this->file('version.php');

        self::assertSame(
            1,
            preg_match('/\$plugin->version\s*=\s*(\d+);/', $version, $pluginversionmatch)
        );
        self::assertGreaterThanOrEqual(
            2026081602,
            (int)$pluginversionmatch[1]
        );
    }
}
