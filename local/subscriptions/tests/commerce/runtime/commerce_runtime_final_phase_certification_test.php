<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\runtime\switching\CommerceRuntimeFinalPhaseAuditor;

final class commerce_runtime_final_phase_certification_test extends \advanced_testcase {
    public function test_phase_h_inventory_and_guards_are_certified(): void {
        $report = (new CommerceRuntimeFinalPhaseAuditor())->audit();

        $this->assertSame(0, $report['errors'], json_encode($report, JSON_PRETTY_PRINT));
        $this->assertTrue($report['certified']);
        $this->assertGreaterThanOrEqual(20, $report['scenario_count']);
    }
}
