<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\reporting\CommerceShadowStatisticsService;

final class commerce_shadow_statistics_test extends \advanced_testcase {
    public function test_summary_groups_classifications_statuses_and_sources(): void {
        $this->resetAfterTest();
        $this->insert_shadow('exec-1', 'purchase-1', 'stripe_webhook', 'equal', 'match', 10, 10);
        $this->insert_shadow('exec-2', 'purchase-2', 'stripe_webhook', 'equivalent', 'representation_only', 10, 11);
        $this->insert_shadow('exec-3', 'purchase-3', 'alfa_webhook', 'different', 'business_difference', 10, 12);

        $summary = (new CommerceShadowStatisticsService())->summarize();

        $this->assertSame(3, $summary->get_total());
        $this->assertSame(1, $summary->get_by_classification()['match']);
        $this->assertSame(1, $summary->get_by_classification()['business_difference']);
        $this->assertSame(2, $summary->get_by_source()['stripe_webhook']);
        $this->assertSame(1000, $summary->get_average_duration_ms());
    }

    private function insert_shadow(
        string $execution,
        string $purchase,
        string $source,
        string $status,
        string $classification,
        int $started,
        int $finished
    ): void {
        global $DB;
        $DB->insert_record('local_subs_commerce_shadow', (object) [
            'executionreference' => $execution,
            'purchasereference' => $purchase,
            'source' => $source,
            'entrypoint' => 'phpunit',
            'comparisonstatus' => $status,
            'classification' => $classification,
            'legacyjson' => '{}',
            'nativejson' => '{}',
            'differencesjson' => '[]',
            'timestarted' => $started,
            'timefinished' => $finished,
            'timecreated' => time(),
        ]);
    }
}
