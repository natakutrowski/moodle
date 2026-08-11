<?php

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\certification\CommerceCertificationReport;

/** @covers \local_subscriptions\commerce\certification\CommerceCertificationReport */
final class commerce_795f7c_checkout_certification_test extends advanced_testcase {
    public function test_inventory_is_exposed_in_machine_readable_report(): void {
        $report = new CommerceCertificationReport('7.95F7C');
        $report->add_inventory('orphan_payments', 0);
        $data = $report->to_array();
        $this->assertTrue($data['certifiable']);
        $this->assertSame(0, $data['inventory']['orphan_payments']);
    }
}
