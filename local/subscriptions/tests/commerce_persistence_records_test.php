<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;

final class commerce_persistence_records_test extends \advanced_testcase {
    public function test_purchase_record_exposes_database_shape(): void {
        $record = new CommercePurchaseRecord(
            str_repeat('a', 32),
            'cmp_' . str_repeat('b', 24),
            'bundle',
            null,
            null,
            96,
            'student@example.com',
            'draft',
            'EUR',
            2000,
            500,
            1500,
            '{}',
            '{}',
            '{}',
            1,
            1700000000,
            1700000100
        );

        $sqlrecord = $record->to_record();
        $this->assertSame(str_repeat('a', 32), $sqlrecord->purchaseuuid);
        $this->assertSame(1500, $sqlrecord->totalminor);
        $this->assertSame('EUR', $sqlrecord->currency);
    }

    public function test_inconsistent_totals_are_rejected(): void {
        $this->expectException(\coding_exception::class);
        new CommercePurchaseRecord(
            str_repeat('a', 32),
            'cmp_' . str_repeat('b', 24),
            'bundle',
            null,
            null,
            null,
            'guest@example.com',
            'draft',
            'EUR',
            2000,
            500,
            1600,
            '{}',
            '{}',
            '{}',
            1,
            null,
            null
        );
    }
}
