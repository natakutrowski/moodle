<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\certification\CommerceCertificationCheck;
use local_subscriptions\commerce\certification\CommerceCertificationReport;

final class commerce_certification_report_test extends advanced_testcase {
    public function test_fail_has_priority_over_warning(): void {
        $report = new CommerceCertificationReport([
            new CommerceCertificationCheck('A', 'ok', CommerceCertificationCheck::PASS, 'OK'),
            new CommerceCertificationCheck('A', 'warning', CommerceCertificationCheck::WARNING, 'Warning'),
            new CommerceCertificationCheck('B', 'failure', CommerceCertificationCheck::FAIL, 'Failure'),
        ], 100, 200);
        $this->assertSame('BLOCKED', $report->global_status());
        $this->assertSame(1, $report->summary()['FAIL']);
    }

    public function test_warning_produces_ready_with_warnings(): void {
        $report = new CommerceCertificationReport([
            new CommerceCertificationCheck('A', 'warning', CommerceCertificationCheck::WARNING, 'Warning'),
        ], 100, 200);
        $this->assertSame('READY_WITH_WARNINGS', $report->global_status());
    }

    public function test_all_pass_produces_ready_for_production(): void {
        $report = new CommerceCertificationReport([
            new CommerceCertificationCheck('A', 'ok', CommerceCertificationCheck::PASS, 'OK'),
        ], 100, 200);
        $this->assertSame('READY_FOR_PRODUCTION', $report->global_status());
        $this->assertStringContainsString('READY_FOR_PRODUCTION', $report->to_markdown());
    }
}
