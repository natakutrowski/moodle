<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\statistics\CommerceStatisticsSeries;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsChartRenderer;

final class commerce_statistics_chart_renderer_test extends \advanced_testcase {
    public function test_product_chart_contains_accessible_fallback(): void {
        global $PAGE;

        $this->resetAfterTest();

        $PAGE->set_url('/local/subscriptions/tests/commerce-statistics-chart.php');
        $renderer = $PAGE->get_renderer('core');

        $series = new CommerceStatisticsSeries(
            'revenue',
            'EUR',
            'day',
            [
                [
                    'timestamp' => 1,
                    'label' => '1 Jan',
                    'value' => 1200,
                ],
            ]
        );

        $html = CommerceStatisticsChartRenderer::product(
            $renderer,
            ['EUR' => $series]
        );

        $this->assertStringContainsString('<details>', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('EUR', $html);
    }
}
