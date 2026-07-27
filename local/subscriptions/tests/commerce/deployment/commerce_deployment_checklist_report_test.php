<?php

// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions\commerce\deployment;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the deployment checklist report.
 *
 * @covers \local_subscriptions\commerce\deployment\CommerceDeploymentChecklistReport
 */
final class commerce_deployment_checklist_report_test extends \advanced_testcase {
    public function test_report_is_ready_when_all_checks_and_acknowledgements_pass(): void {
        $report = CommerceDeploymentChecklistReport::start();
        $report->add_automated_check('readiness', true, 'OK');
        $report->add_operator_acknowledgement('backup', true, 'OK');
        $report->finish();

        $data = $report->to_array();
        $this->assertTrue($report->is_ready());
        $this->assertSame('passed', $data['status']);
        $this->assertSame(0, $data['summary']['automated_failed']);
        $this->assertSame(0, $data['summary']['operator_acknowledgements_pending']);
    }

    public function test_report_is_blocked_by_failed_automated_check(): void {
        $report = CommerceDeploymentChecklistReport::start();
        $report->add_automated_check('integrity', false, 'Mismatch');
        $report->add_operator_acknowledgement('backup', true, 'OK');
        $report->finish();

        $this->assertFalse($report->is_ready());
        $this->assertSame(1, $report->to_array()['summary']['automated_failed']);
    }

    public function test_report_is_blocked_by_pending_acknowledgement(): void {
        $report = CommerceDeploymentChecklistReport::start();
        $report->add_automated_check('readiness', true, 'OK');
        $report->add_operator_acknowledgement('rollback', false, 'Pending');
        $report->finish();

        $this->assertFalse($report->is_ready());
        $this->assertSame(1, $report->to_array()['summary']['operator_acknowledgements_pending']);
    }
}
