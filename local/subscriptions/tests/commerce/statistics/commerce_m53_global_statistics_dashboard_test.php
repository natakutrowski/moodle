<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\statistics\CommerceGlobalStatisticsDashboardRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;

final class commerce_m53_global_statistics_dashboard_test extends \advanced_testcase {
    public function test_global_dashboard_source_contract_keeps_pending_out_of_paid_revenue(): void {
        global $CFG;
        $repository=(string)file_get_contents($CFG->dirroot.'/local/subscriptions/classes/commerce/statistics/CommerceGlobalStatisticsDashboardRepository.php');
        $renderer=(string)file_get_contents($CFG->dirroot.'/local/subscriptions/classes/crm/commerce/statistics/CommerceGlobalStatisticsDashboardRenderer.php');
        $page=(string)file_get_contents($CFG->dirroot.'/local/subscriptions/admin/commerce/statistics/index.php');
        $export=(string)file_get_contents($CFG->dirroot.'/local/subscriptions/admin/commerce/statistics/export.php');

        self::assertStringContainsString("pay.status IN ('paid','succeeded','completed','captured')",$repository);
        self::assertStringContainsString('latest payment attempt',$repository);
        self::assertStringContainsString('paymentattempts',$repository);
        self::assertStringContainsString('acquisition_breakdown',$repository);
        self::assertStringContainsString('provider_breakdown',$repository);
        self::assertStringContainsString('conic-gradient',$renderer);
        self::assertStringContainsString('m53-revenue-mode',$renderer);
        self::assertStringContainsString('CommerceStatisticsPeriodResolver::resolve',$page);
        self::assertStringContainsString('commerce_global_statistics.css',$page);
        self::assertStringContainsString('MoodleExcelWorkbook',$export);
        self::assertStringContainsString('payment_rows',$export);
        self::assertStringContainsString('manual_grant_rows',$export);
        self::assertStringContainsString('product_payment_breakdown',$repository);
        self::assertStringContainsString('m53-tree-branches',$renderer);
        self::assertStringContainsString('m53-tree-main-connector',$renderer);
        self::assertStringContainsString('m53-tree-failure-subtree',$renderer);
        self::assertStringContainsString('m53-payment-tree-summary',$renderer);
        self::assertStringContainsString('premium_tree_node',$renderer);
        $css=(string)file_get_contents($CFG->dirroot.'/local/subscriptions/styles/commerce_global_statistics.css');
        self::assertStringContainsString('M5.3E — connector geometry final fix',$css);
        self::assertStringContainsString('--m53-junction-size',$css);
        self::assertStringContainsString('margin: 0 0 0 37.5%',$css);
        self::assertStringContainsString('.m53-tree-failure-branches::after',$css);
        self::assertStringContainsString('product_payments',$renderer);
        $sharedrenderer=(string)file_get_contents($CFG->dirroot.'/local/subscriptions/classes/crm/commerce/statistics/CommerceStatisticsBreakdownRenderer.php');
        self::assertStringContainsString('commerce-stat-acquisition-row',$sharedrenderer);
        self::assertStringContainsString('commerce-stat-provider-row',$sharedrenderer);
        self::assertStringNotContainsString('m53-delivery-panel',$renderer);
        self::assertStringContainsString("acquisitions['free']",$repository);
    }

    public function test_snapshot_is_empty_on_empty_database(): void {
        global $DB;
        $this->resetAfterTest(true);
        $snapshot=(new CommerceGlobalStatisticsDashboardRepository($DB))->snapshot(
            CommerceStatisticsPeriod::last_days(7)
        );
        self::assertSame(0,$snapshot['global']['paidorders']);
        self::assertSame(0,$snapshot['global']['soldquantity']);
        self::assertSame(0,$snapshot['global']['manualgrants']);
        self::assertSame(0,$snapshot['global']['paymentattempts']);
        self::assertSame(0.0,$snapshot['global']['paymentsuccessrate']);
        self::assertSame([],$snapshot['currencies']);
    }
}
