<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\audit\CommerceIntegrityAuditReport;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationResult;

final class commerce_integrity_audit_report_test extends advanced_testcase {
    public function test_clean_report_is_ready(): void {
        $this->resetAfterTest(true);
        $report = CommerceIntegrityAuditReport::start(['subscription']);
        $report->set_legacy_total('subscription', 1);
        $report->record_results('subscription', [
            new CommerceLegacyMigrationResult(
                'subscription',
                10,
                CommerceLegacyMigrationResult::STATUS_ALREADY_PRESENT
            ),
        ]);
        $report->finish();
        $this->assertTrue($report->is_ready());
        $this->assertSame(1, $report->to_array()['summary']['healthy']);
    }

    public function test_missing_and_mismatch_are_blocking(): void {
        $this->resetAfterTest(true);
        $report = CommerceIntegrityAuditReport::start(['subscription']);
        $report->set_legacy_total('subscription', 2);
        $report->record_results('subscription', [
            new CommerceLegacyMigrationResult(
                'subscription', 10, CommerceLegacyMigrationResult::STATUS_DRY_RUN
            ),
            new CommerceLegacyMigrationResult(
                'subscription', 11, CommerceLegacyMigrationResult::STATUS_INVALID
            ),
        ]);
        $report->finish();
        $data = $report->to_array();
        $this->assertFalse($report->is_ready());
        $this->assertSame(1, $data['summary']['missing_native']);
        $this->assertSame(1, $data['summary']['mismatched']);
        $this->assertCount(2, $data['anomalies']);
    }

    public function test_native_duplicates_are_blocking(): void {
        $this->resetAfterTest(true);
        $report = CommerceIntegrityAuditReport::start(['digital']);
        $report->set_native_integrity('duplicate_references', [
            ['reference' => 'cmp_duplicate', 'duplicatecount' => 2],
        ]);
        $report->finish();
        $this->assertFalse($report->is_ready());
        $this->assertSame(1, $report->to_array()['summary']['duplicate_groups']);
    }
}
