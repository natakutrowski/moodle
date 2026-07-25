<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationIssue;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationResult;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationSummary;

final class commerce_legacy_migration_value_objects_test extends advanced_testcase {
    public function test_result_and_summary_expose_stable_counters(): void {
        $summary = new CommerceLegacyMigrationSummary();
        $summary->add(new CommerceLegacyMigrationResult('subscription', 1, CommerceLegacyMigrationResult::STATUS_MIGRATED));
        $summary->add(new CommerceLegacyMigrationResult('digital', 2, CommerceLegacyMigrationResult::STATUS_ALREADY_PRESENT));
        $summary->add(new CommerceLegacyMigrationResult('digital', 3, CommerceLegacyMigrationResult::STATUS_FAILED, null, null, [
            new CommerceLegacyMigrationIssue('failure', 'Failure.', CommerceLegacyMigrationIssue::SEVERITY_ERROR),
        ]));

        $this->assertSame(3, $summary->get_total());
        $this->assertSame(1, $summary->get_migrated());
        $this->assertSame(1, $summary->get_already_present());
        $this->assertSame(1, $summary->get_failed());
        $this->assertTrue($summary->has_failures());
    }

    public function test_issue_validates_its_severity(): void {
        $this->expectException(\coding_exception::class);
        new CommerceLegacyMigrationIssue('invalid', 'Message', 'fatal');
    }
}
