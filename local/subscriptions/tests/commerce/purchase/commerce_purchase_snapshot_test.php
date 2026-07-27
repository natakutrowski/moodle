<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\value\CommercePurchaseSnapshot;

/** @coversDefaultClass \,local_subscriptions\commerce\domain\value\CommercePurchaseSnapshot */
final class commerce_purchase_snapshot_test extends advanced_testcase {

    public function test_commercial_context_is_preserved(): void {
        $snapshot = new CommercePurchaseSnapshot(
            'bundle-a2-premium',
            'Pack A2 Premium',
            '2026-07',
            ['rule' => 'fixed_bundle_price'],
            ['courseids' => [17], 'digitalproductids' => [8, 9]],
            ['accessduration' => 'P1Y'],
            ['campaign' => 'summer']
        );

        $this->assertSame('bundle-a2-premium', $snapshot->get_offer_reference());
        $this->assertSame('Pack A2 Premium', $snapshot->get_offer_label());
        $this->assertSame('2026-07', $snapshot->get_offer_version());
        $this->assertSame('fixed_bundle_price', $snapshot->get_pricing_context_value('rule'));
        $this->assertSame([17], $snapshot->get_fulfillment_context_value('courseids'));
        $this->assertSame('P1Y', $snapshot->get_term('accessduration'));
    }

    public function test_offer_reference_is_required(): void {
        $this->expectException(\coding_exception::class);
        new CommercePurchaseSnapshot('', 'Offer');
    }

    public function test_offer_label_is_required(): void {
        $this->expectException(\coding_exception::class);
        new CommercePurchaseSnapshot('offer-1', '   ');
    }
}
