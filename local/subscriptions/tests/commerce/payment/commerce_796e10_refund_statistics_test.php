<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e10_refund_statistics_test extends advanced_testcase {
    public function test_statistics_count_succeeded_refunds_by_refund_date(): void {
        global $CFG;

        $repo = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/statistics/'
            . 'CommerceGlobalStatisticsDashboardRepository.php'
        );

        $this->assertStringContainsString(
            'r.timecreated >= :start',
            $repo
        );
        $this->assertStringContainsString(
            "r.status = 'succeeded'",
            $repo
        );
        $this->assertStringContainsString(
            "'netrevenueminor'",
            $repo
        );
    }

    public function test_statistics_renderer_displays_refunds_and_net_revenue(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/crm/commerce/statistics/'
            . 'CommerceGlobalStatisticsDashboardRenderer.php'
        );

        $this->assertStringContainsString(
            'commerce_refund_statistics_refunds',
            $renderer
        );
        $this->assertStringContainsString(
            'commerce_refund_statistics_net',
            $renderer
        );
    }
}
