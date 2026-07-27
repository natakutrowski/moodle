<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\fulfillment\native\audit\CommerceNativeFulfillmentPhaseAuditor;

final class commerce_native_fulfillment_phase_certification_test extends advanced_testcase {
    public function test_native_fulfillment_phase_is_certified(): void {
        $result = (new CommerceNativeFulfillmentPhaseAuditor())->run();
        $this->assertTrue($result['certified'], implode("\n", $result['errors']));
        $this->assertNotContains(false, $result['checks'], 'At least one certification check failed.');
    }
}
