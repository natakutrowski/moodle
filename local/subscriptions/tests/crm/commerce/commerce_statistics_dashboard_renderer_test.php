<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\statistics\CommerceStatisticsComparison;
use local_subscriptions\commerce\statistics\CommerceStatisticsMetric;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsSnapshot;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsDrilldown;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsFilterRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsPageRenderer;
use moodle_url;

final class commerce_statistics_dashboard_renderer_test extends advanced_testcase {
    public function test_dashboard_keeps_currencies_in_separate_sections(): void {
        $this->resetAfterTest();
        $period = CommerceStatisticsPeriod::custom(1000, 2000);
        $snapshot = new CommerceStatisticsSnapshot($period, $period->previous());
        foreach (['EUR' => 12500, 'RUB' => 910000] as $currency => $amount) {
            $snapshot->add(new CommerceStatisticsMetric('net_paid_minor', CommerceStatisticsComparison::compare($amount, $amount - 100), $currency));
            $snapshot->add(new CommerceStatisticsMetric('orders', CommerceStatisticsComparison::compare(2, 1), $currency));
            $snapshot->add(new CommerceStatisticsMetric('average_order_minor', CommerceStatisticsComparison::compare($amount / 2, $amount / 2), $currency));
            $snapshot->add(new CommerceStatisticsMetric('customers', CommerceStatisticsComparison::compare(2, 1), $currency));
        }
        $html = CommerceStatisticsPageRenderer::dashboard($snapshot);
        $this->assertStringContainsString('EUR', $html);
        $this->assertStringContainsString('RUB', $html);
        $this->assertSame(2, substr_count($html, 'crm-commerce-statistics-currency'));
        $this->assertStringContainsString('aria-label=', $html);
    }

    public function test_filter_renderer_escapes_and_exposes_accessible_labels(): void {
        $this->resetAfterTest();
        $html = CommerceStatisticsFilterRenderer::render(new moodle_url('/test.php'), 30, 'EUR', 'stripe');
        $this->assertStringContainsString('name="days"', $html);
        $this->assertStringContainsString('name="currency"', $html);
        $this->assertStringContainsString('name="provider"', $html);
        $this->assertStringContainsString('<label', $html);
    }

    public function test_drilldown_uses_existing_operational_pages(): void {
        $this->resetAfterTest();
        $url = CommerceStatisticsDrilldown::metric_url('failed_payments', 'EUR')->out(false);
        $this->assertStringContainsString('/local/subscriptions/admin/digital/purchases/index.php', $url);
        $this->assertStringContainsString('status=failed', $url);
        $this->assertStringContainsString('currency=EUR', $url);
    }
}
