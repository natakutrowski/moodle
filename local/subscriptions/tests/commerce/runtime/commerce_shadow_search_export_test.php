<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\reporting\CommerceShadowReportExporter;
use local_subscriptions\commerce\shadow\reporting\CommerceShadowSearchCriteria;
use local_subscriptions\commerce\shadow\reporting\CommerceShadowSearchService;

final class commerce_shadow_search_export_test extends \advanced_testcase {
    public function test_search_filters_purchase_user_family_and_classification(): void {
        $this->resetAfterTest();
        $this->insert_shadow('exec-1', 'purchase-course', 'match', 7, 'course_access');
        $this->insert_shadow('exec-2', 'purchase-digital', 'business_difference', 8, 'digital_download');

        $service = new CommerceShadowSearchService();
        $course = $service->search(new CommerceShadowSearchCriteria(
            'purchase-course', null, null, null, 'match', 7, 'subscription'
        ));
        $digital = $service->search(new CommerceShadowSearchCriteria(
            null, null, null, null, 'business_difference', 8, 'digital'
        ));

        $this->assertCount(1, $course);
        $this->assertSame('purchase-course', $course[0]['purchasereference']);
        $this->assertCount(1, $digital);
        $this->assertSame('purchase-digital', $digital[0]['purchasereference']);
    }

    public function test_exporter_outputs_deterministic_json_and_csv(): void {
        $rows = [[
            'id' => 1,
            'executionreference' => 'exec-1',
            'purchasereference' => 'purchase-1',
            'source' => 'stripe_webhook',
            'entrypoint' => 'phpunit',
            'comparisonstatus' => 'different',
            'classification' => 'business_difference',
            'durationms' => 1000,
            'errorclass' => null,
            'errormessage' => null,
            'timecreated' => 123,
            'differences' => ['courseid'],
        ]];
        $exporter = new CommerceShadowReportExporter();
        $json = $exporter->export_json($rows);
        $csv = $exporter->export_csv($rows);

        $this->assertStringContainsString('"purchasereference": "purchase-1"', $json);
        $this->assertStringContainsString('executionreference,purchasereference', $csv);
        $this->assertStringContainsString('business_difference', $csv);
    }

    private function insert_shadow(
        string $execution,
        string $purchase,
        string $classification,
        int $userid,
        string $type
    ): void {
        global $DB;
        $effects = [[
            'type' => $type,
            'resourcekey' => $type === 'course_access' ? 'course:17:full' : 'digital-product:2',
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => 'user@example.com',
            'attributes' => [],
        ]];
        $legacy = json_encode(['effects' => $effects]);
        $native = json_encode(['effects' => $effects]);
        $DB->insert_record('local_subs_commerce_shadow', (object) [
            'executionreference' => $execution,
            'purchasereference' => $purchase,
            'source' => 'stripe_webhook',
            'entrypoint' => 'phpunit',
            'comparisonstatus' => $classification === 'match' ? 'equal' : 'different',
            'classification' => $classification,
            'legacyjson' => $legacy,
            'nativejson' => $native,
            'differencesjson' => '[]',
            'timestarted' => 10,
            'timefinished' => 10,
            'timecreated' => time(),
        ]);
    }
}
