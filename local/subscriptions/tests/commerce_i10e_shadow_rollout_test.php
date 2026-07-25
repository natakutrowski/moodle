<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
use local_subscriptions\commerce\rollout\CommerceShadowRolloutReport;
final class commerce_i10e_shadow_rollout_test extends advanced_testcase {
    public function test_report_aggregates_issues(): void {
        $report = new CommerceShadowRolloutReport([['issues' => 0], ['issues' => 2]]);
        $this->assertSame(2, $report->get_issue_count()); $this->assertFalse($report->is_equal());
    }
}
