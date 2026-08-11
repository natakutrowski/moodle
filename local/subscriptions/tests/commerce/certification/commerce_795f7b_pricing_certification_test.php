<?php

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\certification\CommerceCertificationReport;

/** @covers \local_subscriptions\commerce\certification\CommerceCertificationReport */
final class commerce_795f7b_pricing_certification_test extends advanced_testcase {
    public function test_report_is_not_certifiable_with_blocking_issue(): void {
        $report = new CommerceCertificationReport('7.95F7B');
        $report->add_issue('blocking', 'test', 'Test issue.');
        $this->assertFalse($report->is_certifiable());
        $this->assertSame(1, $report->to_array()['summary']['blocking']);
    }

    public function test_report_remains_certifiable_with_important_issue(): void {
        $report = new CommerceCertificationReport('7.95F7B');
        $report->add_issue('important', 'test', 'Test issue.');
        $this->assertTrue($report->is_certifiable());
    }
}
