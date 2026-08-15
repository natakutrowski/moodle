<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\statistics\CommerceStatisticsProductCanonicalizer;

final class commerce_statistics_product_canonicalizer_n28_test extends \advanced_testcase {
    public function test_legacy_and_native_snapshots_resolve_to_same_catalogue_product(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'COURSE.A1.FULL',
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'A1 Full',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_subs_commerce_prod_map', (object)[
            'productid' => $productid,
            'legacyfamily' => 'subscription',
            'legacytable' => 'subscription_plan',
            'legacyid' => 42,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $canonicalizer = new CommerceStatisticsProductCanonicalizer($DB);

        $native = $canonicalizer->canonicalise(
            'COURSE.A1.FULL',
            'A1 Full',
            json_encode(['catalogue_sku' => 'COURSE.A1.FULL'], JSON_THROW_ON_ERROR)
        );
        $legacy = $canonicalizer->canonicalise(
            'subscription-plan:42',
            'A1 Full',
            json_encode([
                'legacy_table' => 'subscription_plan',
                'legacy_id' => 42,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame($native['key'], $legacy['key']);
        self::assertGreaterThan($legacy['priority'], $native['priority']);
        self::assertSame('COURSE.A1.FULL', $legacy['reference']);
        self::assertSame('A1 Full', $legacy['label']);
    }
}
