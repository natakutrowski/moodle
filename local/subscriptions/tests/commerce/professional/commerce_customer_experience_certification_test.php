<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\professional\certification\CommerceCustomerExperienceCertificationService;

final class commerce_customer_experience_certification_test extends \advanced_testcase {
    public function test_customer_experience_contract_is_complete(): void {
        $this->resetAfterTest();
        $report = (new CommerceCustomerExperienceCertificationService())->certify();
        $this->assertSame(0, $report['errors']);
        $this->assertSame('CERTIFIED', $report['status']);
        $this->assertGreaterThanOrEqual(6, count($report['checks']));
    }
}
