<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\commerce\CrmCommerceCustomerSnapshot;
use local_subscriptions\crm\commerce\CrmCommerceSnapshotSource;
use local_subscriptions\crm\commerce\shadow\CrmCommerceSnapshotComparator;

/**
 * Tests for CRM Commerce snapshot comparisons.
 *
 * @covers \local_subscriptions\crm\commerce\shadow\CrmCommerceSnapshotDifference
 * @covers \local_subscriptions\crm\commerce\shadow\CrmCommerceSnapshotComparison
 * @covers \local_subscriptions\crm\commerce\shadow\CrmCommerceSnapshotComparator
 */
final class crm_commerce_snapshot_comparator_test
    extends advanced_testcase {

    public function test_equivalent_snapshots_have_no_difference(): void {
        $commerce = $this->create_snapshot(
            CrmCommerceSnapshotSource::COMMERCE_DOMAIN
        );

        $legacy = $this->create_snapshot(
            CrmCommerceSnapshotSource::LEGACY_FALLBACK
        );

        $comparator =
            new CrmCommerceSnapshotComparator();

        $comparison = $comparator->compare(
            $commerce,
            $legacy
        );

        $this->assertTrue(
            $comparison->is_equivalent()
        );

        $this->assertSame(
            0,
            $comparison->get_difference_count()
        );
    }

    public function test_revenue_difference_is_detected(): void {
        $commerce = $this->create_snapshot(
            CrmCommerceSnapshotSource::COMMERCE_DOMAIN,
            [
                'EUR' => 13900,
            ]
        );

        $legacy = $this->create_snapshot(
            CrmCommerceSnapshotSource::LEGACY_FALLBACK,
            [
                'EUR' => 12000,
            ]
        );

        $comparator =
            new CrmCommerceSnapshotComparator();

        $comparison = $comparator->compare(
            $commerce,
            $legacy
        );

        $this->assertFalse(
            $comparison->is_equivalent()
        );

        $this->assertSame(
            1,
            $comparison->get_difference_count()
        );

        $this->assertSame(
            'revenue_by_currency',
            $comparison
                ->get_differences()[0]
                ->get_field()
        );
    }

    private function create_snapshot(
        string $source,
        array $revenue = [
            'EUR' => 13900,
        ]
    ): CrmCommerceCustomerSnapshot {
        return new CrmCommerceCustomerSnapshot(
            96,
            [],
            1,
            1,
            $revenue,
            [
                'stripe' => 2,
            ],
            [
                'active' => 1,
                'completed' => 1,
            ],
            1700000000,
            1700000100,
            $source
        );
    }
}