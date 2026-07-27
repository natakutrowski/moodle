<?php

// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the final 7.94I6 production certification report.
 */
final class commerce_production_certification_report_test extends \advanced_testcase {
    public function test_report_is_certified_when_all_checks_pass(): void {
        $report = CommerceProductionCertificationReport::start([
            'git_branch' => 'release/commerce-7.94',
            'git_commit' => str_repeat('a', 40),
        ]);
        $report->add_check('first', true, 'Passed.');
        $report->add_check('second', true, 'Passed.');
        $report->finish();

        $data = $report->to_array();
        $this->assertTrue($report->is_certified());
        $this->assertSame('7.94I6', $data['phase']);
        $this->assertSame('passed', $data['status']);
        $this->assertSame(0, $data['summary']['failed']);
        $this->assertStringContainsString('aaaaaaaaaaaa', $data['certification_id']);
    }

    public function test_report_is_blocked_when_a_check_fails(): void {
        $report = CommerceProductionCertificationReport::start([
            'git_branch' => 'release/commerce-7.94',
            'git_commit' => str_repeat('b', 40),
        ]);
        $report->add_check('first', true, 'Passed.');
        $report->add_check('second', false, 'Blocked.');
        $report->finish();

        $data = $report->to_array();
        $this->assertFalse($report->is_certified());
        $this->assertSame('blocked', $data['status']);
        $this->assertSame(1, $data['summary']['failed']);
    }

    public function test_report_is_read_only(): void {
        $report = CommerceProductionCertificationReport::start([]);
        $report->add_check('only', true, 'Passed.');
        $report->finish();

        $this->assertTrue($report->to_array()['readonly']);
    }
}
