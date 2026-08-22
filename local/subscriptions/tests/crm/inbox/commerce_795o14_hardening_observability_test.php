<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o14_hardening_observability_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_scheduled_task_isolates_account_and_folder_failures(): void {
        $task = $this->file(
            'classes/task/sync_crm_inbox_task.php'
        );

        self::assertStringContainsString(
            'One broken folder must not stop other folders',
            $task
        );

        self::assertStringContainsString(
            'one mailbox must never starve every other Inbox account',
            $task
        );

        self::assertStringContainsString(
            '$iterations < 100',
            $task
        );

        self::assertStringContainsString(
            '$result->cursor === $previouscursor',
            $task
        );
    }

    public function test_stale_sync_runs_can_be_recovered(): void {
        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxSyncLogRepository.php'
        );

        self::assertStringContainsString(
            'close_stale_running(',
            $repository
        );

        self::assertStringContainsString(
            'stale_running_count(',
            $repository
        );

        self::assertStringContainsString(
            "status' => 'failed'",
            $repository
        );
    }

    public function test_diagnostics_checks_integrity_and_sent_copy_health(): void {
        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxDiagnosticsRepository.php'
        );

        foreach (
            [
                'duplicate_identity_count(',
                'orphan_remote_count(',
                'sent_copy_failure_count(',
                'last_successful_sync_at(',
            ]
            as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $repository
            );
        }

        $service = $this->file(
            'classes/crm/inbox/services/InboxDiagnosticsService.php'
        );

        self::assertStringContainsString(
            "'sync_freshness'",
            $service
        );

        self::assertStringContainsString(
            "'data_integrity'",
            $service
        );

        self::assertStringContainsString(
            "'sent_copy_health'",
            $service
        );
    }

    public function test_diagnostics_renderer_has_operational_dashboard(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxDiagnosticsRenderer.php'
        );

        self::assertStringContainsString(
            'operational_health(',
            $renderer
        );

        self::assertStringContainsString(
            'crm-inbox-o14-health-grid',
            $renderer
        );

        self::assertStringContainsString(
            'crm-inbox-o14-log-row',
            $renderer
        );
    }
}
