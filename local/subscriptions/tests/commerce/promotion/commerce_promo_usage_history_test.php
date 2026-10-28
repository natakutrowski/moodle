<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\promotion;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\promotion\report\CommercePromotionUsageReport;

/** CRM promotion history: exact attribution, legacy fallback and settlement. */
final class commerce_promo_usage_history_test extends \advanced_testcase {
    public function test_exact_snapshot_attribution_uses_promotion_identity_and_discount_amount(): void {
        $metadata = [
            'promotion_codes' => ['PARTNER10'],
            'cart_adjustment_discount_minor' => 4500,
            'promotion_adjustments' => [
                ['promotionid' => 23, 'code' => 'PARTNER10', 'discountminor' => 2800, 'automatic' => false],
                ['promotionid' => 24, 'code' => null, 'discountminor' => 1700, 'automatic' => true],
            ],
        ];
        self::assertTrue(CommercePromotionUsageReport::matches($metadata, 'PARTNER10', 23));
        self::assertFalse(CommercePromotionUsageReport::matches($metadata, 'OTHER', 25));
        self::assertSame(['amount' => 2800, 'precision' => 'exact'],
            CommercePromotionUsageReport::discount_for_code($metadata, 'PARTNER10', 23));
        // A code renamed since the sale is still attributable by the saved ID.
        self::assertTrue(CommercePromotionUsageReport::matches($metadata, 'RENAMED', 23));
    }

    public function test_legacy_discount_is_marked_as_estimate_or_unavailable(): void {
        $metadata = ['promotion_codes' => ['AD10'], 'cart_adjustment_discount_minor' => 2700];
        self::assertTrue(CommercePromotionUsageReport::matches($metadata, 'AD10', 6));
        self::assertSame(['amount' => 2700, 'precision' => 'estimate'],
            CommercePromotionUsageReport::discount_for_code($metadata, 'AD10', 6));
        $metadata['promotion_codes'][] = 'OTHER';
        self::assertSame(['amount' => null, 'precision' => 'unavailable'],
            CommercePromotionUsageReport::discount_for_code($metadata, 'AD10', 6));
    }

    public function test_multiple_attempts_are_counted_once_and_successful_refunds_reduce_commission(): void {
        $payments = [
            (object)['id' => 2, 'status' => 'failed', 'amountminor' => 20000, 'paidat' => null, 'timemodified' => 100],
            (object)['id' => 3, 'status' => 'paid', 'amountminor' => 24000, 'paidat' => 200, 'timemodified' => 200],
        ];
        $result = CommercePromotionUsageReport::financial_state(24000, 'completed', $payments, [3 => 5000]);
        self::assertSame('partial', $result['status']);
        self::assertSame(24000, $result['grossminor']);
        self::assertSame(5000, $result['refundedminor']);
        self::assertSame(19000, $result['netminor']);
        self::assertSame(200, $result['paidat']);
        self::assertSame(2850, CommercePromotionUsageReport::commission_minor(19000, 1500));
    }

    public function test_payment_is_not_counted_before_confirmation(): void {
        $payments = [(object)['id' => 8, 'status' => 'redirected', 'amountminor' => 28000]];
        $result = CommercePromotionUsageReport::financial_state(28000, 'payment_pending', $payments, []);
        self::assertSame('pending', $result['status']);
        self::assertSame(0, $result['netminor']);
        self::assertSame(0, CommercePromotionUsageReport::commission_minor(0, 2000));
    }

    public function test_full_refund_without_local_refund_record_has_zero_commission(): void {
        $payments = [(object)['id' => 8, 'status' => 'refunded', 'amountminor' => 28000,
            'paidat' => 100, 'timemodified' => 200]];
        $result = CommercePromotionUsageReport::financial_state(28000, 'completed', $payments, []);
        self::assertSame('refunded', $result['status']);
        self::assertSame(0, $result['netminor']);
    }

    public function test_unknown_partial_refund_excludes_unreconciled_commission(): void {
        $payments = [(object)['id' => 8, 'status' => 'partially_refunded', 'amountminor' => 28000,
            'paidat' => 100, 'timemodified' => 200]];
        $result = CommercePromotionUsageReport::financial_state(28000, 'completed', $payments, []);
        self::assertSame('unknown', $result['status']);
        self::assertSame(0, $result['netminor']);
    }
}
