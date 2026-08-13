<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriodResolver;

final class commerce_m51_product_statistics_dashboard_test extends \advanced_testcase {
    public function test_period_resolver_supports_today_seven_days_and_custom_ranges(): void {
        $this->resetAfterTest(true); $now=make_timestamp(2026,8,12,15,30,0);
        $today=CommerceStatisticsPeriodResolver::resolve('today','','',$now); self::assertSame('hour',CommerceStatisticsPeriodResolver::granularity($today));
        $seven=CommerceStatisticsPeriodResolver::resolve('7','','',$now); self::assertSame('day',CommerceStatisticsPeriodResolver::granularity($seven));
        $custom=CommerceStatisticsPeriodResolver::resolve('custom','2026-08-01','2026-08-03',$now); self::assertGreaterThan(2*DAYSECS,$custom->duration());

        $todayprevious=CommerceStatisticsPeriodResolver::previous('today',$today);
        self::assertNotNull($todayprevious);
        self::assertSame($today->start()-DAYSECS,$todayprevious->start());
        self::assertSame($today->end()-DAYSECS,$todayprevious->end());

        $sevenprevious=CommerceStatisticsPeriodResolver::previous('7',$seven);
        self::assertNotNull($sevenprevious);
        self::assertSame($seven->start()-(7*DAYSECS),$sevenprevious->start());
        self::assertSame($seven->end()-(7*DAYSECS),$sevenprevious->end());

        self::assertNull(CommerceStatisticsPeriodResolver::previous(
            'all',
            CommerceStatisticsPeriodResolver::resolve('all','','',$now)
        ));
    }
    public function test_product_dashboard_is_explicitly_payment_aware_and_counts_manual_grants(): void {
        $root=dirname(__DIR__,3);$repo=file_get_contents($root.'/classes/commerce/statistics/product/CommerceProductStatisticsDashboardRepository.php');$view=file_get_contents($root.'/admin/commerce/products/view.php');$renderer=file_get_contents($root.'/classes/crm/commerce/statistics/CommerceProductStatisticsDashboardRenderer.php');
        self::assertStringContainsString("pay.status IN ('paid','succeeded','completed','captured')",$repo);
        self::assertStringContainsString("'crm_manual_grant'",file_get_contents($root.'/classes/commerce/grant/CommerceManualProductGrantService.php'));
        self::assertStringContainsString('crm_manual_grant',$repo);
        self::assertStringContainsString('CommerceStatisticsPeriodResolver::options()',$view);
        self::assertStringContainsString('statistics_export.php',$view);
        self::assertStringContainsString('conic-gradient',$renderer);
        self::assertStringContainsString('m51-payment-pie',$renderer);
        self::assertStringContainsString('currency_flag',$renderer);
        self::assertStringContainsString('commerce_m51_trend_new',$renderer);
        self::assertStringContainsString('set_stacked',$renderer);
        self::assertStringContainsString('CommerceStatisticsPeriodResolver::previous',$view);
        self::assertStringContainsString('m52-revenue-mode',$renderer);
        self::assertStringContainsString('cumulativevalues',$renderer);
        self::assertStringContainsString('provider_breakdown',$repo);
        self::assertStringContainsString('acquisition_breakdown',$repo);
        self::assertStringContainsString('paymentsuccessrate',$repo);
        self::assertStringContainsString('int|float $current',$renderer);
        self::assertStringContainsString('payment_journey',$renderer);
        $sharedrenderer=(string)file_get_contents($root.'/classes/crm/commerce/statistics/CommerceStatisticsBreakdownRenderer.php');
        self::assertStringContainsString('commerce-stat-acquisition-row',$sharedrenderer);
        self::assertStringContainsString('commerce-stat-provider-row',$sharedrenderer);
        self::assertStringNotContainsString('m52-funnel-card',$renderer);
        self::assertStringContainsString('product_tree_node',$renderer);
        self::assertStringContainsString('(float)$previous === 0.0',$renderer);
    }
    public function test_export_is_real_xlsx_and_keeps_orders_and_grants_separate(): void {
        $root=dirname(__DIR__,3);$export=file_get_contents($root.'/admin/commerce/products/statistics_export.php');self::assertStringContainsString("excellib.class.php",$export);self::assertStringContainsString('MoodleExcelWorkbook',$export);self::assertStringContainsString('order_rows',$export);self::assertStringContainsString('manual_grant_rows',$export);self::assertStringContainsString('payment_rows',$export);self::assertStringContainsString('commerce_m52_export_payments',$export);
    }
}
