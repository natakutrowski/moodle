<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\certification\phaseh\CommercePhaseHCertificationReport;
use local_subscriptions\commerce\certification\phaseh\CommercePhaseHCertifier;

final class commerce_795h7_phase_h_certification_test extends \advanced_testcase {
    public function test_architecture_only_phase_h_certification_passes(): void {
        global $DB;
        $this->resetAfterTest(true);

        $result = (new CommercePhaseHCertifier($DB, dirname(__DIR__, 3)))->certify()->to_array();

        $this->assertTrue($result['certified']);
        $this->assertSame(0, $result['summary']['fail']);
        $this->assertGreaterThan(0, $result['summary']['pass']);
        $this->assertGreaterThan(0, $result['summary']['skip']);
    }

    public function test_report_counts_each_status(): void {
        $report = new CommercePhaseHCertificationReport(true, [[
            'key' => 'sample',
            'label' => 'Sample',
            'checks' => [
                ['key' => 'a', 'status' => 'PASS', 'message' => '', 'details' => []],
                ['key' => 'b', 'status' => 'WARN', 'message' => '', 'details' => []],
                ['key' => 'c', 'status' => 'SKIP', 'message' => '', 'details' => []],
            ],
        ]]);
        $result = $report->to_array();

        $this->assertSame(['pass' => 1, 'warn' => 1, 'fail' => 0, 'skip' => 1], $result['summary']);
    }

    public function test_phase_h_cli_exposes_real_transaction_selectors(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/cli/commerce/certification/certify_phase_h.php');

        $this->assertIsString($source);
        $this->assertStringContainsString('COMMERCE 7.95H CERTIFIED', $source);
        $this->assertStringContainsString("'reference' => ''", $source);
        $this->assertStringContainsString("'purchase' => ''", $source);
        $this->assertStringContainsString("'payment' => ''", $source);
        $this->assertStringContainsString("'session' => ''", $source);
        $this->assertStringContainsString("'course' => ''", $source);
        $this->assertStringContainsString("'digital' => ''", $source);
        $this->assertStringContainsString("'bundle' => ''", $source);
    }
}
