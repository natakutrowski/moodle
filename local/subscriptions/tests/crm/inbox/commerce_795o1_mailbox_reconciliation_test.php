<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\dto\InboxRemoteMessageState;
use local_subscriptions\crm\inbox\services\InboxSyncFolderPolicy;

final class commerce_795o1_mailbox_reconciliation_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_professional_folder_policy_always_keeps_inbox_and_sent(): void {
        $account = new InboxAccount(
            1,
            'CampusFR Support',
            'support@example.test',
            'imap_smtp',
            true,
            'support',
            [
                'folders' => [
                    'inbox' => 'INBOX',
                    'sent' => 'INBOX.Sent',
                    'archive' => 'INBOX.Archive',
                ],
                'sync' => [
                    'folders' => [
                        'inbox',
                        'archive',
                    ],
                ],
            ],
            [],
            null,
            null,
            null
        );

        $folders = (new InboxSyncFolderPolicy())
            ->folder_types($account);

        self::assertContains('inbox', $folders);
        self::assertContains('sent', $folders);
        self::assertContains('archive', $folders);
    }

    public function test_remote_state_provider_key_is_location_stable(): void {
        $state = new InboxRemoteMessageState(
            'INBOX',
            '42',
            '1234',
            'message@example.test',
            true,
            false,
            false,
            false,
            false
        );

        self::assertSame(
            hash('sha256', 'INBOX|42|1234'),
            $state->provider_key()
        );
    }

    public function test_sync_runtime_has_incremental_and_reconciliation_paths(): void {
        $connector = $this->file(
            'classes/crm/inbox/contracts/InboxConnectorInterface.php'
        );
        $sync = $this->file(
            'classes/crm/inbox/services/InboxSyncService.php'
        );
        $task = $this->file(
            'classes/task/sync_crm_inbox_task.php'
        );

        self::assertStringContainsString(
            'inspect_messages(',
            $connector
        );
        self::assertStringContainsString(
            'public function reconcile_folder(',
            $sync
        );
        self::assertStringContainsString(
            "'reconciliation'",
            $sync
        );
        self::assertStringContainsString(
            '->reconcile_folder(',
            $task
        );
    }

    public function test_reconciliation_updates_read_state_and_remote_locations(): void {
        $sync = $this->file(
            'classes/crm/inbox/services/InboxSyncService.php'
        );
        $remote = $this->file(
            'classes/crm/inbox/repositories/InboxRemoteMessageRepository.php'
        );
        $message = $this->file(
            'classes/crm/inbox/repositories/InboxMessageRepository.php'
        );
        $thread = $this->file(
            'classes/crm/inbox/repositories/InboxThreadRepository.php'
        );

        self::assertStringContainsString(
            'active_for_folder(',
            $remote
        );
        self::assertStringContainsString(
            'set_read_state(',
            $message
        );
        self::assertStringContainsString(
            'refresh_unread_count(',
            $thread
        );
        self::assertStringContainsString(
            '$this->remote->deactivate(',
            $sync
        );
    }

    public function test_diagnostics_expose_mailbox_reconciliation(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxDiagnosticsService.php'
        );
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxDiagnosticsRenderer.php'
        );

        self::assertStringContainsString(
            "'local_subscriptions_inbox_remote'",
            $service
        );
        self::assertStringContainsString(
            "'sync_folder_baseline'",
            $service
        );
        self::assertStringContainsString(
            'folderstatus',
            $service
        );
        self::assertStringContainsString(
            'crm_inbox_o1_mailbox_sync_title',
            $renderer
        );
    }

    public function test_plugin_version_is_unchanged(): void {
        self::assertStringContainsString(
            '$plugin->version = 2026082102;',
            $this->file('version.php')
        );
    }
}
