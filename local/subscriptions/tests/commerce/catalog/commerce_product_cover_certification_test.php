<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\catalog\cover\CommerceProductCoverCertificationService;

final class commerce_product_cover_certification_test extends \advanced_testcase {
    public function test_multicover_and_activation_layout_contract_is_certified(): void {
        $service = new CommerceProductCoverCertificationService();
        $findings = $service->certify();

        $this->assertFalse($service->has_errors($findings));
        $this->assertCount(4, $findings);
        foreach ($findings as $finding) {
            $this->assertSame('ok', $finding['status'], $finding['label'] . ': ' . $finding['detail']);
        }
    }
}
