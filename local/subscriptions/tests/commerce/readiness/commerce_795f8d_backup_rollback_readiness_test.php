<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\readiness\CommerceBackupRollbackReadinessAuditor;

final class commerce_795f8d_backup_rollback_readiness_test extends advanced_testcase {
    public function test_missing_backup_evidence_is_blocking(): void {
        global $CFG;
        $this->resetAfterTest();

        $auditor = new CommerceBackupRollbackReadinessAuditor(
            $CFG->dirroot,
            $CFG->dirroot . '/local/subscriptions',
            static fn(string $command): array => ['code' => 1, 'output' => ''],
            static fn(string $path): int => 20 * 1073741824
        );
        $data = $auditor->audit([], '', 24, 5)->to_array();

        $this->assertFalse($data['certifiable']);
        $codes = array_column($data['issues'], 'code');
        $this->assertContains('missing_database_backup_evidence', $codes);
        $this->assertContains('invalid_rollback_ref', $codes);
    }

    public function test_fresh_nonempty_files_are_accepted_as_evidence(): void {
        global $CFG;
        $this->resetAfterTest();

        $directory = make_request_directory();
        $files = [];
        foreach (['database', 'code', 'moodledata'] as $type) {
            $files[$type] = $directory . '/' . $type . '.backup';
            file_put_contents($files[$type], 'backup');
        }
        $auditor = new CommerceBackupRollbackReadinessAuditor(
            $CFG->dirroot,
            $CFG->dirroot . '/local/subscriptions',
            static fn(string $command): array => ['code' => 0, 'output' => str_repeat('a', 40)],
            static fn(string $path): int => 20 * 1073741824
        );
        $data = $auditor->audit($files, 'release/commerce-7.95', 24, 5)->to_array();

        $codes = array_column($data['issues'], 'code');
        $this->assertNotContains('missing_database_backup_evidence', $codes);
        $this->assertNotContains('invalid_rollback_ref', $codes);
    }
}
