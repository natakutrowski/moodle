<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\migration\CommerceLegacyBackfillReport;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationResult;

final class commerce_legacy_backfill_report_test extends advanced_testcase {
    public function test_report_persists_checkpoint_and_can_resume(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $path = make_request_directory() . '/backfill.json';
        $report = CommerceLegacyBackfillReport::start('session-test', true, ['subscription', 'digital'], 100, 0, 0);
        $report->record_batch('subscription', [
            new CommerceLegacyMigrationResult('subscription', 10, CommerceLegacyMigrationResult::STATUS_MIGRATED),
            new CommerceLegacyMigrationResult('subscription', 11, CommerceLegacyMigrationResult::STATUS_ALREADY_PRESENT),
        ], 11);
        $report->save($path);

        $loaded = CommerceLegacyBackfillReport::load($path);
        $loaded->assert_compatible(true, ['digital', 'subscription'], 100, 0);

        $this->assertSame('session-test', $loaded->get_session_id());
        $this->assertSame(11, $loaded->get_last_processed_id('subscription'));
        $this->assertSame(2, $loaded->get_processed('subscription'));
        $this->assertFalse($loaded->is_family_complete('subscription'));
        $this->assertFileExists($path);
    }

    public function test_report_rejects_incompatible_resume_options(): void {
        $this->resetAfterTest(true);
        $report = CommerceLegacyBackfillReport::start('session-test', false, ['subscription'], 50, 0, 10);

        $this->expectException(\RuntimeException::class);
        $report->assert_compatible(true, ['subscription'], 50, 10);
    }

    public function test_report_tracks_failures_without_losing_cursor(): void {
        $this->resetAfterTest(true);
        $report = CommerceLegacyBackfillReport::start('session-test', true, ['digital'], 25, 0, 0);
        $report->record_batch('digital', [
            new CommerceLegacyMigrationResult('digital', 7, CommerceLegacyMigrationResult::STATUS_FAILED),
        ], 7);
        $report->finish(false);

        $data = $report->to_array();
        $this->assertSame(7, $report->get_last_processed_id('digital'));
        $this->assertSame('completed_with_errors', $data['status']);
        $this->assertCount(1, $data['errors']);
        $this->assertSame(1, $data['families']['digital']['counters']['failed']);
    }
}
