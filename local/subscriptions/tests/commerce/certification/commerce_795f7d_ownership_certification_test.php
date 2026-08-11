<?php

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\certification\CommerceCertificationReport;

/** @covers \local_subscriptions\commerce\certification\CommerceCertificationReport */
final class commerce_795f7d_ownership_certification_test extends advanced_testcase {
    public function test_multiple_issue_severities_are_counted(): void {
        $report = new CommerceCertificationReport('7.95F7D');
        $report->add_issue('blocking', 'a', 'A');
        $report->add_issue('important', 'b', 'B');
        $data = $report->to_array();
        $this->assertSame(2, $data['summary']['total']);
        $this->assertSame(1, $data['summary']['blocking']);
        $this->assertSame(1, $data['summary']['important']);
    }
}
