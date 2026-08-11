<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\certification\CommerceCertificationReport;

final class commerce_certification_report_test extends advanced_testcase {
    public function test_blocking_issue_prevents_certification(): void {
        $report = new CommerceCertificationReport('test-phase');
        $report->add_inventory('records', 3);
        $report->add_issue('important', 'warning', 'Warning');
        $report->add_issue('blocking', 'failure', 'Failure');

        $result = $report->to_array();

        $this->assertFalse($report->is_certifiable());
        $this->assertFalse($result['certifiable']);
        $this->assertSame('test-phase', $result['phase']);
        $this->assertSame(1, $result['summary']['blocking']);
        $this->assertSame(1, $result['summary']['important']);
        $this->assertSame(2, $result['summary']['total']);
        $this->assertSame(3, $result['inventory']['records']);
    }

    public function test_important_issue_keeps_report_certifiable(): void {
        $report = new CommerceCertificationReport('test-phase');
        $report->add_issue('important', 'warning', 'Warning');

        $result = $report->to_array();

        $this->assertTrue($report->is_certifiable());
        $this->assertTrue($result['certifiable']);
        $this->assertSame(0, $result['summary']['blocking']);
        $this->assertSame(1, $result['summary']['important']);
    }

    public function test_empty_report_is_certifiable(): void {
        $report = new CommerceCertificationReport('test-phase');
        $result = $report->to_array();

        $this->assertTrue($report->is_certifiable());
        $this->assertTrue($result['certifiable']);
        $this->assertSame(0, $result['summary']['total']);
        $this->assertSame([], $result['issues']);
    }
}
