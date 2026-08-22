<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o1_3_folder_discovery_and_diagnostics_ux_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_manual_sync_discovers_remote_folders_before_using_configuration(): void {
        $service = $this->file('classes/crm/inbox/services/InboxManualSyncService.php');
        self::assertStringContainsString('InboxFolderDiscoveryService $discovery', $service);
        self::assertStringContainsString('$this->discovery->discover(', $service);
    }

    public function test_scheduled_sync_discovers_remote_folders_too(): void {
        $task = $this->file('classes/task/sync_crm_inbox_task.php');
        self::assertStringContainsString('$runtime->discovery->discover(', $task);
    }

    public function test_new_accounts_do_not_hardcode_provider_folder_names(): void {
        $service = $this->file('classes/crm/inbox/services/InboxAccountService.php');
        self::assertStringContainsString("'sent' => ''", $service);
        self::assertStringContainsString("'trash' => ''", $service);
        self::assertStringContainsString("'drafts' => ''", $service);
    }

    public function test_diagnostics_persists_discovered_folder_mapping(): void {
        $service = $this->file('classes/crm/inbox/services/InboxDiagnosticsService.php');
        self::assertStringContainsString('new InboxFolderDiscoveryService(', $service);
        self::assertStringContainsString('->discover(', $service);
    }
}
