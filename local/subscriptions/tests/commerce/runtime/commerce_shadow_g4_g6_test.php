<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceLegacyFulfillmentObservation;
use local_subscriptions\commerce\shadow\CommerceShadowComparator;
use local_subscriptions\commerce\shadow\CommerceShadowComparison;
use local_subscriptions\commerce\shadow\CommerceShadowDivergenceClassifier;
use local_subscriptions\commerce\shadow\CommerceShadowEffect;
use local_subscriptions\commerce\shadow\CommerceShadowExecutionReport;
use local_subscriptions\commerce\shadow\CommerceShadowSource;

final class commerce_shadow_g4_g6_test extends \advanced_testcase {
    public function test_divergence_classifier_maps_all_comparison_statuses(): void {
        $classifier = new CommerceShadowDivergenceClassifier();
        $expected = [
            CommerceShadowComparison::EQUAL => CommerceShadowDivergenceClassifier::MATCH,
            CommerceShadowComparison::EQUIVALENT => CommerceShadowDivergenceClassifier::REPRESENTATION_ONLY,
            CommerceShadowComparison::DIFFERENT => CommerceShadowDivergenceClassifier::BUSINESS_DIFFERENCE,
            CommerceShadowComparison::NOT_COMPARABLE => CommerceShadowDivergenceClassifier::NOT_COMPARABLE,
            CommerceShadowComparison::SHADOW_ERROR => CommerceShadowDivergenceClassifier::SHADOW_FAILURE,
        ];
        foreach ($expected as $status => $classification) {
            $this->assertSame($classification, $classifier->classify(new CommerceShadowComparison('purchase-1', $status)));
        }
    }

    public function test_comparison_persistence_table_accepts_immutable_run(): void {
        global $DB;
        $this->resetAfterTest();
        $record = (object) [
            'executionreference' => 'shadow-test-1', 'purchasereference' => 'purchase-1',
            'source' => CommerceShadowSource::STRIPE_WEBHOOK, 'entrypoint' => 'phpunit',
            'comparisonstatus' => CommerceShadowComparison::EQUAL, 'classification' => CommerceShadowDivergenceClassifier::MATCH,
            'legacyjson' => '{}', 'nativejson' => '{}', 'differencesjson' => '[]',
            'timestarted' => time(), 'timefinished' => time(), 'timecreated' => time(),
        ];
        $id = $DB->insert_record('local_subs_commerce_shadow', $record);
        $this->assertGreaterThan(0, $id);
        $this->assertSame(1, $DB->count_records('local_subs_commerce_shadow'));
    }


    public function test_execution_report_exposes_persistence_timestamps(): void {
        $report = new CommerceShadowExecutionReport(
            'purchase-1',
            CommerceShadowSource::STRIPE_WEBHOOK,
            'exec-1',
            123,
            456,
            [],
            []
        );

        $this->assertSame(123, $report->get_started_at());
        $this->assertSame(456, $report->get_finished_at());
    }

    public function test_comparator_still_classifies_business_difference(): void {
        $legacy = new CommerceLegacyFulfillmentObservation('purchase-1', CommerceShadowSource::STRIPE_WEBHOOK, [
            new CommerceShadowEffect('g1', 'course_access', 'course:17:full', 7, 'u@example.com', ['courseid' => 17]),
        ]);
        $native = new CommerceShadowExecutionReport('purchase-1', CommerceShadowSource::STRIPE_WEBHOOK, 'exec', 1, 1, [], [
            new CommerceShadowEffect('g1', 'course_access', 'course:18:full', 7, 'u@example.com', ['courseid' => 18]),
        ]);
        $comparison = (new CommerceShadowComparator())->compare($legacy, $native);
        $this->assertSame(CommerceShadowComparison::DIFFERENT, $comparison->get_status());
    }

    public function test_digital_download_implementation_metadata_is_business_equivalent(): void {
        $legacy = new CommerceLegacyFulfillmentObservation('purchase-1', CommerceShadowSource::REPAIR_JOB, [
            new CommerceShadowEffect(
                'g1',
                'digital_download',
                'digital-product:verbes-3e-groupe',
                113,
                'u@example.com',
                ['active' => true, 'resourcekey' => 'digital-product:verbes-3e-groupe']
            ),
        ]);
        $native = new CommerceShadowExecutionReport('purchase-1', CommerceShadowSource::REPAIR_JOB, 'exec', 1, 1, [], [
            new CommerceShadowEffect(
                'g1',
                'digital_download',
                'digital-product:verbes-3e-groupe',
                113,
                'u@example.com',
                [
                    'beneficiaryemail' => 'u@example.com',
                    'beneficiaryuserid' => 113,
                    'maxdownloads' => null,
                    'productsku' => 'DIGITAL-PRODUCT.VERBES-3E-GROUPE',
                    'resourcekey' => 'digital-product:verbes-3e-groupe',
                    'validfrom' => 1785146187,
                    'validuntil' => null,
                ]
            ),
        ]);

        $comparison = (new CommerceShadowComparator())->compare($legacy, $native);

        $this->assertSame(CommerceShadowComparison::EQUIVALENT, $comparison->get_status());
        $this->assertSame([], $comparison->get_differences());
    }
}
