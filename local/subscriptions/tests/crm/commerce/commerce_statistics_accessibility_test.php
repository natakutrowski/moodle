<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_statistics_accessibility_test extends \advanced_testcase {
    public function test_chart_renderer_declares_table_headers_and_caption(): void {
        global $CFG;

        $path = $CFG->dirroot
            . '/local/subscriptions/classes/crm/commerce/statistics/CommerceStatisticsChartRenderer.php';
        $source = file_get_contents($path);

        $this->assertIsString($source);
        $this->assertStringContainsString("html_writer::tag('caption'", $source);
        $this->assertStringContainsString("['scope' => 'col']", $source);
        $this->assertStringContainsString("['scope' => 'row']", $source);
        $this->assertStringContainsString("html_writer::tag('details'", $source);
    }
}
