<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\certification\CommerceCustomerCrmCertificationService;

final class commerce_customer_crm_certification_test extends \advanced_testcase {
    public function test_required_native_crm_contract_is_certified(): void {
        global $DB;
        $this->resetAfterTest(true);
        $report = (new CommerceCustomerCrmCertificationService($DB))->certify();
        $this->assertSame(0, $report->error_count());
        $this->assertTrue($report->is_certified(true));
        $this->assertGreaterThanOrEqual(7, count($report->findings));
    }
}
