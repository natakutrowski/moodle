<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\readiness\CommerceProductionReadinessAuditor;

final class commerce_795f8e_production_readiness_test extends advanced_testcase {
    public function test_global_report_exposes_all_certification_phases(): void {
        global $CFG, $DB;
        $this->resetAfterTest();

        $data = (new CommerceProductionReadinessAuditor(
            $DB,
            $CFG->dirroot,
            $CFG->dirroot . '/local/subscriptions'
        ))->audit([
            'branch' => '',
            'mode' => 'native',
            'family' => 'all',
            'batch_size' => 10,
        ]);

        $this->assertSame('7.95F8E', $data['phase']);
        $this->assertTrue($data['readonly']);
        $this->assertArrayHasKey('F8A Git', $data['phases']);
        $this->assertArrayHasKey('F8D Backup & rollback', $data['phases']);
        $this->assertArrayHasKey('F7F UX', $data['phases']);
        $this->assertArrayHasKey('git_commit', $data['environment']);
    }
}
